<?php

namespace App\Http\Controllers\Web;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\DeleteInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Actions\Billing\PostPayment;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::with(['client', 'project'])->orderByDesc('created_at')->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('invoices.create', compact('clients', 'projects'));
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $createInvoice): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $createInvoice->handle($request->validated());

        return redirect()->route('invoices.show', $invoice);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'client', 'project']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $invoice->load('items');

        return view('invoices.edit', compact('invoice', 'clients', 'projects'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, UpdateInvoice $updateInvoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $updateInvoice->handle($invoice, $request->validated());

        return redirect()->route('invoices.show', $invoice);
    }

    public function destroy(Invoice $invoice, DeleteInvoice $deleteInvoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $deleteInvoice->handle($invoice);

        return redirect()->route('invoices.index');
    }

    public function storePayment(Request $request, Invoice $invoice, PostPayment $postPayment)
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'provider' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $postPayment->handle($invoice, $data);

        if ($request->expectsJson()) {
            return response()->json([
                'payment_id' => $payment->id,
                'amount_cents' => $payment->amount_cents,
            ]);
        }

        return redirect()->route('invoices.show', $invoice);
    }
}
