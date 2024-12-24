<?php

// Invoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'issue_date',
        'due_date',
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
