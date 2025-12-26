<?php

namespace App\Http\Controllers\Web;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\DeleteInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Actions\Billing\PostPayment;
use App\Actions\Billing\SendInvoice;
use App\Actions\Billing\VoidInvoice;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use App\Services\InvoicePdfService;

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
        $defaultCurrency = auth()->user()?->currentOrganization?->default_currency ?? config('stripe.default_currency', 'USD');

        return view('invoices.create', compact('clients', 'projects', 'defaultCurrency'));
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

        $invoice->load(['items', 'client', 'project', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $invoice->load('items');
        $defaultCurrency = auth()->user()?->currentOrganization?->default_currency ?? config('stripe.default_currency', 'USD');

        return view('invoices.edit', compact('invoice', 'clients', 'projects', 'defaultCurrency'));
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
            'method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
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

    public function send(Invoice $invoice, SendInvoice $sendInvoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $sendInvoice->handle($invoice);

        return redirect()->route('invoices.show', $invoice);
    }

    public function void(Invoice $invoice, VoidInvoice $voidInvoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $voidInvoice->handle($invoice);

        return redirect()->route('invoices.show', $invoice);
    }


    public function pdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $this->authorize('view', $invoice);

        $pdf = app(InvoicePdfService::class)->render($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . ($invoice->invoice_number ?? $invoice->id) . '.pdf"',
        ]);
    }


    // Snapshot method removed as it is now in actions
}
