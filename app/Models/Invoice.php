<?php

// Invoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function workLogs()
    {
        return $this->belongsToMany(WorkLog::class)->withTimestamps();
    }
}
