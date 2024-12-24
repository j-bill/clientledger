<?php

// Project.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'customer_id',
        'hourly_rate',
        'deadline',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
