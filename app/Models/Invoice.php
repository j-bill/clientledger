<?php

// Invoice.php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'invoice_number', // User must provide this now
        'issue_date',
        'due_date',       // User must provide this now
        'total_amount',
        'status',
        'notes',
        'pdf_path',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Tax figures are derived from the global tax_rate setting and always
     * shipped alongside the stored amounts in API responses.
     *
     * @var list<string>
     */
    protected $appends = ['tax_rate', 'tax_amount', 'total_with_tax'];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsToMany<WorkLog, $this>
     */
    public function workLogs(): BelongsToMany
    {
        return $this->belongsToMany(WorkLog::class)->withTimestamps();
    }

    /**
     * Current global tax rate in percent, memoized for the request so
     * serializing a list of invoices costs a single settings query.
     */
    public static function currentTaxRate(): float
    {
        return once(function (): float {
            $raw = Setting::where('key', 'tax_rate')->value('value');

            return is_numeric($raw) ? (float) $raw : 0.0;
        });
    }

    /**
     * @return Attribute<float, never>
     */
    protected function taxRate(): Attribute
    {
        return Attribute::get(fn (): float => self::currentTaxRate());
    }

    /**
     * @return Attribute<float, never>
     */
    protected function taxAmount(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->total_amount * (self::currentTaxRate() / 100));
    }

    /**
     * @return Attribute<float, never>
     */
    protected function totalWithTax(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->total_amount * (1 + self::currentTaxRate() / 100));
    }
}
