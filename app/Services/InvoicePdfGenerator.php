<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class InvoicePdfGenerator
{
    /**
     * Generate PDF for an invoice
     *
     * @return ($download is true ? Response : \Barryvdh\DomPDF\PDF)
     */
    public function generate(Invoice $invoice, bool $download = false): \Barryvdh\DomPDF\PDF|Response
    {
        // Load all settings
        $settings = $this->getSettings();

        // Get and set the application language
        $language = $settings['language'] ?? 'en';
        if (is_string($language)) {
            app()->setLocale($language);
        }

        // Get tax rate from settings
        $taxRateSetting = $settings['tax_rate'] ?? 0;
        $taxRate = is_numeric($taxRateSetting) ? floatval($taxRateSetting) : 0.0;

        // Check if we have multiple users
        $userCount = User::count();

        // Load invoice with related data and order work logs by ID
        $invoice->load(['customer', 'workLogs' => function (BelongsToMany $query) {
            $query->orderBy('id', 'asc');
        }]);

        // Override global settings with customer-level values if set
        if ($invoice->customer) {
            if ($invoice->customer->invoice_default_message) {
                $settings['invoice_default_message'] = $invoice->customer->invoice_default_message;
            }
            if ($invoice->customer->invoice_payment_terms) {
                $settings['invoice_payment_terms'] = $invoice->customer->invoice_payment_terms;
            }
        }

        // Prepare invoice data with translations
        $data = [
            'invoice' => $invoice,
            'company' => $settings,
            'currency_symbol' => $settings['currency_symbol'] ?? '$',
            'date_format' => $settings['date_format'] ?? 'DD/MM/YYYY',
            'tax_rate' => $taxRate,
            'multiple_users' => $userCount > 1,
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
                    'f' => function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics) use ($pageInfoColumns) {
                        // Set the page number text with translation
                        $pageText = __('notifications.invoice.page');
                        $ofText = __('notifications.invoice.of');
                        $pageNumberText = "$pageText $pageNumber $ofText $pageCount";

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
    public function stream(Invoice $invoice): Response
    {
        return $this->generate($invoice)->stream();
    }

    /**
     * Generate and download PDF
     */
    public function download(Invoice $invoice): Response
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

        // Ensure invoices directory exists (Storage::disk() without arguments
        // resolves to the configured default disk, i.e. filesystems.default)
        $disk = Storage::disk();
        $invoicesPath = $disk->path('invoices');
        if (! file_exists($invoicesPath)) {
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
     *
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        $settings = [];

        foreach (Setting::pluck('value', 'key')->toArray() as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            // Parse JSON values if needed
            if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                $decoded = json_decode($value, true);
                $value = $decoded !== null ? $decoded : $value;
            }

            $settings[$key] = $value;
        }

        return $settings;
    }
}
