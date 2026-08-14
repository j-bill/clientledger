<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Enums\Unit;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfGenerator
{
    /** Fallback used when no valid accent color is configured */
    private const DEFAULT_ACCENT_COLOR = '#333333';

    /** Allowed invoice font choices mapped to CSS font stacks */
    private const FONTS = [
        'arial' => 'Arial, sans-serif',
        'helvetica' => "Helvetica, 'Helvetica Neue', Arial, sans-serif",
        'georgia' => 'Georgia, serif',
        'times' => "'Times New Roman', Times, serif",
        'courier' => "'Courier New', Courier, monospace",
    ];

    /**
     * Self-hosted web fonts (woff2 in resources/fonts, embedded as base64
     * data URIs so PDF rendering needs no network access)
     */
    private const WEB_FONTS = [
        'inter' => ['family' => 'Inter', 'stack' => "'Inter', Arial, sans-serif"],
        'lato' => ['family' => 'Lato', 'stack' => "'Lato', Arial, sans-serif"],
        'montserrat' => ['family' => 'Montserrat', 'stack' => "'Montserrat', Arial, sans-serif"],
        'merriweather' => ['family' => 'Merriweather', 'stack' => "'Merriweather', Georgia, serif"],
        'playfair' => ['family' => 'Playfair Display', 'stack' => "'Playfair Display', Georgia, serif"],
    ];

    /**
     * Build the PDF for an invoice (rendered by headless Chrome via Browsershot)
     *
     * @param  array<string, mixed>  $settingsOverrides  Ad-hoc overrides on top of stored settings (used for previews)
     */
    public function generate(Invoice $invoice, array $settingsOverrides = []): PdfBuilder
    {
        // Load all settings
        $settings = array_merge($this->getSettings(), $settingsOverrides);

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

        // Load invoice with related data and order work logs by ID.
        // Unsaved invoices (e.g. preview samples) already carry their relations.
        if ($invoice->exists) {
            $invoice->load(['customer', 'items', 'workLogs' => function (BelongsToMany $query) {
                $query->orderBy('id', 'asc');
            }]);
        }

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
            'appearance' => $this->resolveAppearance($settings),
        ];

        return Pdf::view('invoices.pdf', $data)
            ->format(Format::A4)
            // Bottom margin reserves space for the footer template
            ->margins(20, 20, 35, 20, Unit::Millimeter)
            ->footerView('invoices.pdf-footer', ['company' => $settings])
            ->withBrowsershot(function (Browsershot $browsershot): void {
                // Chrome refuses to sandbox when running as root (e.g. in containers)
                $browsershot->noSandbox()->showBackground();
            });
    }

    /**
     * Build a preview PDF from a sample invoice, applying ad-hoc settings
     * overrides (e.g. unsaved appearance options from the settings page)
     *
     * @param  array<string, mixed>  $settingsOverrides
     */
    public function preview(array $settingsOverrides = []): PdfBuilder
    {
        return $this->generate($this->buildSampleInvoice(), $settingsOverrides);
    }

    /**
     * In-memory sample invoice used for appearance previews (never persisted)
     */
    private function buildSampleInvoice(): Invoice
    {
        $customer = new Customer([
            'name' => 'Acme Corporation',
            'address_line_1' => 'Example Street 42',
            'postcode' => '10115',
            'city' => 'Berlin',
            'contact_email' => 'billing@acme.example',
        ]);

        $project = new Project(['name' => 'Website Relaunch']);
        $user = new User(['name' => 'Jane Doe']);

        $workLogDefinitions = [
            ['description' => "Implemented responsive landing page\nIncluding hero section and pricing table", 'hours' => 6.5],
            ['description' => 'API integration for the contact form with spam protection and validation', 'hours' => 3.0],
            ['description' => 'Code review and deployment', 'hours' => 1.5],
        ];

        $workLogs = collect();
        foreach ($workLogDefinitions as $index => $definition) {
            $workLog = new WorkLog([
                'date' => now()->subDays(7 - $index),
                'description' => $definition['description'],
                'hourly_rate' => 95,
                'hours_worked' => $definition['hours'],
                'start_time' => '09:00',
                'end_time' => '14:00',
            ]);
            $workLog->setRelation('project', $project);
            $workLog->setRelation('user', $user);
            $workLogs->push($workLog);
        }

        $items = collect([
            new InvoiceItem([
                'description' => 'Stock photo license',
                'quantity' => 1,
                'unit_price' => 49,
            ]),
        ]);

        $invoice = new Invoice([
            'invoice_number' => 'PREVIEW-0001',
            'issue_date' => now(),
        ]);
        $invoice->setRelation('customer', $customer);
        $invoice->setRelation('workLogs', $workLogs);
        $invoice->setRelation('items', $items);

        return $invoice;
    }

    /**
     * Generate and stream PDF for viewing
     */
    public function stream(Invoice $invoice): Response
    {
        return $this->generate($invoice)
            ->name("invoice-{$invoice->invoice_number}.pdf")
            ->inline()
            ->toResponse(request());
    }

    /**
     * Generate and download PDF
     */
    public function download(Invoice $invoice): Response
    {
        return $this->generate($invoice)
            ->name("invoice-{$invoice->invoice_number}.pdf")
            ->download()
            ->toResponse(request());
    }

    /**
     * Save PDF to Laravel storage (for permanent storage)
     */
    public function saveToStorage(Invoice $invoice): string
    {
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$filename}";

        $defaultDisk = config('filesystems.default');

        $this->generate($invoice)
            ->disk(is_string($defaultDisk) ? $defaultDisk : 'local')
            ->save($path);

        return $path;
    }

    /**
     * Resolve the invoice appearance settings into validated CSS values.
     *
     * Values come from user-editable settings, so anything unexpected
     * falls back to the default look.
     *
     * @param  array<string, mixed>  $settings
     * @return array{accent: string, accent_text: string, font_family: string, density: string, table_style: string}
     */
    private function resolveAppearance(array $settings): array
    {
        $accent = $settings['invoice_accent_color'] ?? '';
        if (! is_string($accent) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = self::DEFAULT_ACCENT_COLOR;
        }

        $font = $settings['invoice_font'] ?? '';
        $fontFaces = '';
        if (is_string($font) && isset(self::WEB_FONTS[$font])) {
            $fontFamily = self::WEB_FONTS[$font]['stack'];
            $fontFaces = $this->buildFontFaces($font, self::WEB_FONTS[$font]['family']);
        } else {
            $fontFamily = is_string($font) && isset(self::FONTS[$font]) ? self::FONTS[$font] : self::FONTS['arial'];
        }

        $density = $settings['invoice_density'] ?? '';
        if ($density !== 'compact') {
            $density = 'comfortable';
        }

        $tableStyle = $settings['invoice_table_style'] ?? '';
        if ($tableStyle !== 'minimal') {
            $tableStyle = 'filled';
        }

        return [
            'accent' => $accent,
            'accent_text' => $this->contrastColor($accent),
            'font_family' => $fontFamily,
            'font_faces' => $fontFaces,
            'density' => $density,
            'table_style' => $tableStyle,
        ];
    }

    /**
     * Build @font-face CSS with base64-embedded woff2 files for a web font.
     *
     * Google's variable fonts ship one file covering all weights, so when the
     * 400 and 700 files are identical a single declaration with a weight
     * range is emitted.
     */
    private function buildFontFaces(string $slug, string $family): string
    {
        $regular = resource_path("fonts/{$slug}-400.woff2");
        $bold = resource_path("fonts/{$slug}-700.woff2");

        if (! is_file($regular)) {
            return '';
        }

        $fontFace = function (string $file, string $weight) use ($family): string {
            $data = base64_encode((string) file_get_contents($file));

            return '@font-face { font-family: \''.$family.'\'; font-style: normal; font-weight: '.$weight.'; '
                ."src: url(data:font/woff2;base64,{$data}) format('woff2'); }\n";
        };

        if (! is_file($bold) || md5_file($regular) === md5_file($bold)) {
            return $fontFace($regular, '100 900');
        }

        return $fontFace($regular, '400').$fontFace($bold, '700');
    }

    /**
     * Black or white, whichever reads better on the given background color
     */
    private function contrastColor(string $hexColor): string
    {
        $red = (int) hexdec(substr($hexColor, 1, 2));
        $green = (int) hexdec(substr($hexColor, 3, 2));
        $blue = (int) hexdec(substr($hexColor, 5, 2));

        // Relative luminance approximation (ITU-R BT.601)
        $luminance = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return $luminance > 0.6 ? '#1a1a1a' : '#ffffff';
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
