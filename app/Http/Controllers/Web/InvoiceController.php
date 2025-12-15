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

        if ($invoice->status !== 'sent') {
            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->save();
        }

        $pdf = $this->generatePdf($invoice);
        Mail::to($invoice->client?->email ?? auth()->user()->email)
            ->queue(new InvoiceSentMail($invoice, $pdf));

        ActivityLogger::log($invoice, 'invoices.sent', $invoice->organization_id, [
            'status' => $invoice->status,
            'sent_at' => $invoice->sent_at?->toIso8601String(),
        ]);

        Log::info('invoice.sent', [
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'status' => $invoice->status,
        ]);

        return redirect()->route('invoices.show', $invoice);
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $pdf = $this->generatePdf($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function generatePdf(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'client', 'project']);
        $content = "%PDF-1.4\n";
        $content .= "1 0 obj <</Type /Catalog /Pages 2 0 R>> endobj\n";
        $content .= "2 0 obj <</Type /Pages /Count 1 /Kids [3 0 R]>> endobj\n";

        $body = "Invoice #{$invoice->invoice_number}\n";
        $body .= "Total: ".number_format($invoice->total_cents / 100, 2)."\n";
        foreach ($invoice->items as $item) {
            $body .= "{$item->description} {$item->quantity} x {$item->unit_price_cents}\n";
        }

        $stream = "BT /F1 12 Tf 50 750 Td ({$this->escapePdfText($body)}) Tj ET";
        $len = strlen($stream);
        $content .= "3 0 obj <</Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources <</Font <</F1 5 0 R>>>>>> endobj\n";
        $content .= "4 0 obj <</Length {$len}>> stream\n{$stream}\nendstream endobj\n";
        $content .= "5 0 obj <</Type /Font /Subtype /Type1 /BaseFont /Helvetica>> endobj\n";
        $content .= "xref\n0 6\n0000000000 65535 f \n";
        $content .= "trailer <</Size 6 /Root 1 0 R>>\nstartxref\n".strlen($content)."\n%%EOF";

        return $content;
    }

    protected function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
