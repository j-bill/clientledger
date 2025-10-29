<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class InvoicePdfGenerator
{
    /**
     * Generate PDF for an invoice
     */
    public function generate(Invoice $invoice, bool $download = false)
    {
        // Load all settings
        $settings = $this->getSettings();

        // Get and set the application language
        $language = $settings['language'] ?? 'en';
        app()->setLocale($language);

        // Get tax rate from settings
        $taxRate = floatval($settings['tax_rate'] ?? 0);

        // Prepare invoice data with translations
        $data = [
            'invoice' => $invoice->load(['customer', 'workLogs']),
            'company' => $settings,
            'currency_symbol' => $settings['currency_symbol'] ?? '$',
            'date_format' => $settings['date_format'] ?? 'DD/MM/YYYY',
            'tax_rate' => $taxRate,
        ];

        // Generate PDF from view
        $pdf = Pdf::loadView('invoices.pdf', $data)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false);

        // Add page numbering callback if page_info is selected in any footer column
        $footerColumns = [
            $settings['invoice_footer_col1'] ?? 'company_info',
            $settings['invoice_footer_col2'] ?? 'bank_info',
            $settings['invoice_footer_col3'] ?? 'page_info',
        ];

        if (in_array('page_info', $footerColumns)) {
            // Get all column indices where page_info is used
            $pageInfoColumns = array_keys($footerColumns, 'page_info');

            $dompdf = $pdf->getDomPDF();
            $dompdf->setCallbacks([
                [
                    'event' => 'end_document',
                    'f' => function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($pageInfoColumns) {
                        // Set the page number text
                        $pageNumberText = "Page $pageNumber of $pageCount";

                        // Set font, font size, and color to match footer
                        $font = $fontMetrics->get_font('Arial', 'normal');
                        $fontSize = 8;
                        // Colors in DomPDF are normalized 0-1, not 0-255
                        // #666 = RGB(102, 102, 102) = (0.4, 0.4, 0.4) normalized
                        $color = [0.4, 0.4, 0.4];

                        // Get the width of the text
                        $textWidth = $fontMetrics->get_text_width($pageNumberText, $font, $fontSize);

                        // A4 page width is 595px, with margins of 40px
                        // Footer is divided into 3 columns, each ~171px wide (515px / 3)
                        // Column positions: 0=left (40), 1=center (211), 2=right (382)
                        $columnWidth = 515 / 3;
                        $columnStartX = [40, 40 + $columnWidth, 40 + (2 * $columnWidth)];
                        $columnEndX = [40 + $columnWidth, 40 + (2 * $columnWidth), 40 + (3 * $columnWidth)];

                        // Y position in footer
                        $y = 772;

                        // Draw page info in all configured columns
                        foreach ($pageInfoColumns as $columnIndex) {
                            // Determine alignment based on column position
                            if ($columnIndex === 0) {
                                // Left column: align left
                                $x = $columnStartX[$columnIndex] + 5; // 5px padding from left
                            } elseif ($columnIndex === 1) {
                                // Center column: center the text
                                $x = $columnStartX[$columnIndex] + ($columnWidth / 2) - ($textWidth / 2);
                            } else { // columnIndex === 2
                                // Right column: align right
                                $x = $columnEndX[$columnIndex] - $textWidth - 5; // 5px padding from right
                            }
                            $canvas->text($x, $y, $pageNumberText, $font, $fontSize, $color);
                        }
                    },
                ],
            ]);
        }

        if ($download) {
            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
        }

        return $pdf;
    }

    /**
     * Generate and stream PDF for viewing
     */
    public function stream(Invoice $invoice)
    {
        return $this->generate($invoice)->stream();
    }

    /**
     * Generate and download PDF
     */
    public function download(Invoice $invoice)
    {
        return $this->generate($invoice, true);
    }

    /**
     * Save PDF to Laravel storage (for permanent storage)
     */
    public function saveToStorage(Invoice $invoice): string
    {
        $pdf = $this->generate($invoice);
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$filename}";

        // Ensure invoices directory exists
        $disk = Storage::disk(config('filesystems.default'));
        $invoicesPath = $disk->path('invoices');
        if (!file_exists($invoicesPath)) {
            mkdir($invoicesPath, 0755, true);
        }

        // Save to Laravel storage
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Save PDF to disk (legacy method, kept for compatibility)
     */
    public function save(Invoice $invoice, string $path = 'invoices'): string
    {
        $pdf = $this->generate($invoice);
        $filename = "invoice-{$invoice->invoice_number}-".now()->timestamp.'.pdf';
        $fullPath = storage_path("app/{$path}/{$filename}");

        // Ensure directory exists
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return "{$path}/{$filename}";
    }

    /**
     * Get all settings as an array
     */
    private function getSettings(): array
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        // Parse JSON values if needed
        return array_map(function ($value) {
            if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                $decoded = json_decode($value, true);

                return $decoded !== null ? $decoded : $value;
            }

            return $value;
        }, $settings);
    }
}
