<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'project_id',
        'description',
        'amount',
        'currency',
        'date',
        'category',
        'receipt_path',
        'is_tax_deductible',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_tax_deductible' => 'boolean',
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
