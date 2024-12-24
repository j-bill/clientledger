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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
