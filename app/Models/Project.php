<?php

// Project.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

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
        'hourly_rate' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_project')
            ->withPivot('hourly_rate')
            ->withTimestamps();
    }

    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
    }

    public function getHourlyRateForUser(User $user)
    {
        $projectUser = $this->users()->where('user_id', $user->id)->first();
        return $projectUser ? $projectUser->pivot->hourly_rate : $this->hourly_rate;
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
