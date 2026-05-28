<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renders a "Счёт на оплату" PDF for a bank-transfer order.
 *
 * Output path:  storage/app/invoices/T-NNNNNN.pdf
 * Returned:     relative path "invoices/T-NNNNNN.pdf" — stored on
 *               Order::$invoice_pdf_path so admin and customer can
 *               download it later.
 *
 * Font: DejaVu Sans, bundled with DomPDF, full Cyrillic coverage.
 */
final class InvoiceGenerator
{
    public function generate(Order $order): string
    {
        if (! $order->relationLoaded('items')) {
            $order->load('items');
        }

        $settings = Setting::current();

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'settings' => $settings,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
        ]);

        $relativePath = 'invoices/'.$order->order_number.'.pdf';

        if (! Storage::disk('local')->put($relativePath, $pdf->output())) {
            throw new RuntimeException("Could not write invoice to disk: $relativePath");
        }

        return $relativePath;
    }
}
