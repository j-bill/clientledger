<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'hourly_rate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hourly_rate' => 'decimal:2',
    ];

    // Relationships
    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'user_project')
            ->withPivot('hourly_rate')
            ->withTimestamps();
    }

    // Role-based access control methods
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isFreelancer()
    {
        return $this->hasRole('freelancer');
    }

    // Scope for freelancers to only see their own data
    public function scopeFreelancer($query)
    {
        return $query->where('role', 'freelancer');
    }

    // Get hourly rate for a specific project
    public function getProjectHourlyRate(Project $project)
    {
        $projectUser = $this->projects()->where('project_id', $project->id)->first();
        return $projectUser ? $projectUser->pivot->hourly_rate : $this->hourly_rate;
    }

    // Calculate total earnings for a period
    public function calculateEarnings($startDate, $endDate)
    {
        return $this->workLogs()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }
}
