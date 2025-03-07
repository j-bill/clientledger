<?php

// WorkLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    protected $fillable = [
        'project_id',
        'date',
        'start_time',
        'end_time',
        'hours_worked',
        'description',
        'billable',
    ];

    // start time and end time should be return in H:i format.
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Project::class, 'id', 'id', 'project_id', 'customer_id');
    }
}
