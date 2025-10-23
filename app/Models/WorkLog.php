<?php

// WorkLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'hours_worked',
        'description',
        'billable',
        'hourly_rate',
        'user_hourly_rate',
    ];

    // start time and end time should be return in H:i format.
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'hourly_rate' => 'decimal:2',
        'user_hourly_rate' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Project::class, 'id', 'id', 'project_id', 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)->withTimestamps();
    }

    // Calculate the amount owed to the user
    public function calculateUserAmount()
    {
        if ($this->hours_worked && $this->user_hourly_rate) {
            return $this->hours_worked * $this->user_hourly_rate;
        }
        return 0;
    }

    // Get the billing rate (what customer pays)
    public function getBillingRate()
    {
        // Use the stored hourly_rate if available, otherwise calculate from project/customer
        if ($this->hourly_rate) {
            return $this->hourly_rate;
        }

        // Fallback: get from project or customer
        if ($this->project?->hourly_rate) {
            return $this->project->hourly_rate;
        }

        if ($this->customer?->hourly_rate) {
            return $this->customer->hourly_rate;
        }

        return 0;
    }

    // Calculate the billing amount (what customer pays)
    public function calculateBillingAmount()
    {
        if ($this->hours_worked) {
            return $this->hours_worked * $this->getBillingRate();
        }
        return 0;
    }

    // Scope for freelancers to only see their own work logs
    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
