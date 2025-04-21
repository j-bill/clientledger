<?php

// Invoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_number', // User must provide this now
        'due_date',       // User must provide this now
        'total_amount',
        'status',
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
