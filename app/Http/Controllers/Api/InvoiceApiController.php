<?php

namespace App\Http\Controllers\Api;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\DeleteInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class InvoiceApiController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::with(['client', 'project'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(10);

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $createInvoice)
    {
        $this->authorize('create', Invoice::class);

        $invoice = $createInvoice->handle($request->validated());

        return (new InvoiceResource($invoice->load(['client', 'project', 'items'])))->response()->setStatusCode(201);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['client', 'project', 'items']));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, UpdateInvoice $updateInvoice)
    {
        $this->authorize('update', $invoice);

        $updateInvoice->handle($invoice, $request->validated());

        return new InvoiceResource($invoice->load(['client', 'project', 'items']));
    }

    public function destroy(Invoice $invoice, DeleteInvoice $deleteInvoice)
    {
        $this->authorize('delete', $invoice);

        $deleteInvoice->handle($invoice);

        return response()->json([], 204);
    }
}
