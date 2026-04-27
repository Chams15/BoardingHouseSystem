<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    public function generateReceiptPdf(Payment $payment): string
    {
        // Get the full payment data with relationships
        $payment->load([
            'bill' => function ($query) {
                $query->with([
                    'leaseContract' => function ($query) {
                        $query->with('tenant', 'room');
                    },
                ]);
            },
        ]);

        // Convert signature image to base64 data URL
        $signaturePath = 'receipts/signature(1).png';
        $signatureUrl = '';
        
        if (Storage::disk('private')->exists($signaturePath)) {
            $signatureContent = Storage::disk('private')->get($signaturePath);
            $signatureUrl = 'data:image/png;base64,' . base64_encode($signatureContent);
        }

        // Generate PDF from view
        $pdf = Pdf::loadView('receipts.payment-receipt', [
            'payment' => $payment,
            'signatureUrl' => $signatureUrl,
        ]);

        // Create storage path if it doesn't exist
        $storagePath = 'receipts';
        if (! Storage::disk('private')->exists($storagePath)) {
            Storage::disk('private')->makeDirectory($storagePath);
        }

        // Generate filename
        $fileName = sprintf(
            'receipt-%d-%d-%s.pdf',
            $payment->payment_id,
            $payment->bill->bill_id,
            now()->format('Y-m-d-His')
        );

        // Save PDF to private storage
        $filePath = $storagePath.'/'.$fileName;
        $pdfContent = $pdf->output();
        Storage::disk('private')->put($filePath, $pdfContent);

        // Return the file path for storing in database
        return $filePath;
    }

    public function getReceiptUrl(Payment $payment): ?string
    {
        if (! $payment->receipt_url) {
            return null;
        }

        // Check if the file exists
        if (! Storage::disk('private')->exists($payment->receipt_url)) {
            return null;
        }

        return route('payments.receipt.download', $payment);
    }

    public function downloadReceipt(Payment $payment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $payment->receipt_url || ! Storage::disk('private')->exists($payment->receipt_url)) {
            abort(404, 'Receipt not found');
        }

        return Storage::disk('private')->download(
            $payment->receipt_url,
            sprintf(
                'receipt-payment-%d-%s.pdf',
                $payment->payment_id,
                now()->format('Y-m-d')
            )
        );
    }
}
