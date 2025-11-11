<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;

class InvoiceNumberGenerator
{
    /**
     * Generate a unique invoice number based on settings
     */
    public static function generate(): string
    {
        $useRandom = self::getSettingValue('invoice_number_random', 'false') === 'true' || 
                     self::getSettingValue('invoice_number_random', 'false') === '1' ||
                     self::getSettingValue('invoice_number_random', 'false') === true;

        if ($useRandom) {
            return self::generateRandomInvoiceNumber();
        } else {
            return self::generateSequentialInvoiceNumber();
        }
    }

    /**
     * Generate a random invoice number with collision checking
     */
    private static function generateRandomInvoiceNumber(): string
    {
        $prefix = self::getSettingValue('invoice_prefix', 'INV-');
        $format = self::getSettingValue('invoice_number_format', 'YYYY-MM-number');
        $length = (int) self::getSettingValue('invoice_number_random_length', '8');
        $maxAttempts = 100;
        $attempt = 0;

        // Ensure length is within reasonable bounds
        $length = max(4, min(20, $length));

        $now = Carbon::now();
        $year = $now->format('Y');
        $month = $now->format('m');

        while ($attempt < $maxAttempts) {
            // Generate a random number with the specified length
            $min = (int) str_pad('1', $length, '0');
            $max = (int) str_pad('9', $length, '9');
            $randomNumber = random_int($min, $max);
            $randomPart = str_pad($randomNumber, $length, '0', STR_PAD_LEFT);

            // Build invoice number based on format
            switch ($format) {
                case 'YYYY-MM-number':
                    $invoiceNumber = $prefix . $year . '-' . $month . '-' . $randomPart;
                    break;

                case 'YYYY-number':
                    $invoiceNumber = $prefix . $year . '-' . $randomPart;
                    break;

                case 'number':
                default:
                    $invoiceNumber = $prefix . $randomPart;
                    break;
            }

            // Check if this number already exists
            if (!Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                return $invoiceNumber;
            }

            $attempt++;
        }

        throw new \Exception('Unable to generate unique random invoice number after ' . $maxAttempts . ' attempts');
    }

    /**
     * Generate a sequential invoice number based on format and starting number
     */
    private static function generateSequentialInvoiceNumber(): string
    {
        $prefix = self::getSettingValue('invoice_prefix', 'INV-');
        $format = self::getSettingValue('invoice_number_format', 'YYYY-MM-number');
        $startNumber = (int) self::getSettingValue('invoice_number_start', '1');

        $now = Carbon::now();
        $year = $now->format('Y');
        $month = $now->format('m');

        switch ($format) {
            case 'YYYY-MM-number':
                // Get the next number for this year-month
                $nextNumber = self::getNextSequentialNumber($year, $month, $startNumber);
                return $prefix . $year . '-' . $month . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            case 'YYYY-number':
                // Get the next number for this year
                $nextNumber = self::getNextSequentialNumberForYear($year, $startNumber);
                return $prefix . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            case 'number':
                // Get the next sequential number
                $nextNumber = self::getNextSequentialNumberGlobal($startNumber);
                return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            default:
                throw new \Exception('Unknown invoice number format: ' . $format);
        }
    }

    /**
     * Get the next sequential number for YYYY-MM-number format
     */
    private static function getNextSequentialNumber(string $year, string $month, int $startNumber): int
    {
        // Find the highest invoice number for this year-month
        $pattern = $year . '-' . $month . '-%';

        $lastInvoice = Invoice::where('invoice_number', 'like', $pattern)
            ->get()
            ->sortByDesc(function ($invoice) {
                // Extract the number part from invoice_number (format: YYYY-MM-001)
                $parts = explode('-', $invoice->invoice_number);
                return (int) array_pop($parts);
            })
            ->first();

        if ($lastInvoice) {
            // Extract the number part from the invoice_number
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumber = (int) array_pop($parts);
            return $lastNumber + 1;
        }

        return $startNumber;
    }

    /**
     * Get the next sequential number for YYYY-number format
     */
    private static function getNextSequentialNumberForYear(string $year, int $startNumber): int
    {
        // Find the highest invoice number for this year
        $pattern = $year . '-%';

        $lastInvoice = Invoice::where('invoice_number', 'like', $pattern)
            ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED) DESC")
            ->first();

        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumber = (int) array_pop($parts);
            return $lastNumber + 1;
        }

        return $startNumber;
    }

    /**
     * Get the next sequential number globally
     */
    private static function getNextSequentialNumberGlobal(int $startNumber): int
    {
        $prefix = self::getSettingValue('invoice_prefix', 'INV-');

        // Find the highest invoice number globally
        $lastInvoice = Invoice::orderByRaw("CAST(SUBSTRING(invoice_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED) DESC")
            ->first();

        if ($lastInvoice) {
            $numberPart = substr($lastInvoice->invoice_number, strlen($prefix));
            $lastNumber = (int) $numberPart;
            return $lastNumber + 1;
        }

        return $startNumber;
    }

    /**
     * Get a setting value, with fallback to default
     */
    private static function getSettingValue(string $key, $default = null)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
