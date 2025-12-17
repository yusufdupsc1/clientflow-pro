<?php

namespace App\Http\Controllers\Web;

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\DeleteInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Actions\Billing\PostPayment;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceSentMail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Support\Activity\ActivityLogger;
use App\Support\Webhooks\WebhookDispatcher;
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

        $invoice->load(['items', 'client', 'project']);

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

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['paid', 'void'], true)) {
            abort(response()->json(['message' => 'Invoice cannot be sent in its current state.'], 422));
        }

        $before = $this->snapshot($invoice);

        if ($invoice->status !== 'sent') {
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();
        }

        $after = $this->snapshot($invoice);
        Mail::to($invoice->client?->email ?? auth()->user()->email)
            ->queue(new InvoiceSentMail($invoice));

        ActivityLogger::log($invoice, 'invoices.sent', $invoice->organization_id, [
            'status' => $invoice->status,
            'sent_at' => $invoice->sent_at?->toIso8601String(),
            'before' => $before,
            'after' => $after,
        ]);

        WebhookDispatcher::dispatch('invoices.sent', $invoice->organization_id, [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
        ]);

        Log::info('invoice.sent', [
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'status' => $invoice->status,
        ]);

        return redirect()->route('invoices.show', $invoice);
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (! in_array($invoice->status, ['sent', 'overdue'], true)) {
            abort(response()->json(['message' => 'Only sent invoices can be voided.'], 422));
        }

        $before = $this->snapshot($invoice);

        $invoice->status = 'void';
        $invoice->paid_at = null;
        $invoice->save();

        $after = $this->snapshot($invoice);

        ActivityLogger::log($invoice, 'invoices.voided', $invoice->organization_id, [
            'before' => $before,
            'after' => $after,
        ]);

        WebhookDispatcher::dispatch('invoices.voided', $invoice->organization_id, [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
        ]);

        return redirect()->route('invoices.show', $invoice);
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $pdf = app(InvoicePdfService::class)->render($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function snapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status,
            'sent_at' => optional($invoice->sent_at)->toIso8601String(),
            'paid_at' => optional($invoice->paid_at)->toIso8601String(),
            'amount_paid_cents' => $invoice->amount_paid_cents,
            'total_cents' => $invoice->total_cents,
        ];
    }
}
