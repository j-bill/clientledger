<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
