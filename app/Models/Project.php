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

    // cast deadline to yyyy-mm-dd format
    protected $casts = [
        'deadline' => 'date:Y-m-d',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
