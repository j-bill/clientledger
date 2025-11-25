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
        'avatar',
        'notify_on_project_assignment',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verification_code',
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
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_device_fingerprints' => 'array',
        'notify_on_project_assignment' => 'boolean',
        'email_verification_code_expires_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['has_two_factor_enabled'];

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

    // 2FA Methods
    public function hasTwoFactorEnabled()
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorEnabled()
    {
        return $this->hasTwoFactorEnabled();
    }

    /**
     * Check if a device is trusted by fingerprint or client fingerprint.
     */
    public function isDeviceTrusted($fingerprint, $clientFingerprint = null)
    {
        if (!$this->two_factor_device_fingerprints) {
            return false;
        }

        $now = now()->timestamp;

        foreach ($this->two_factor_device_fingerprints as $device) {
            // Skip expired devices
            if ($device['expires_at'] <= $now) {
                continue;
            }
            
            // Check server-side fingerprint match
            if ($device['fingerprint'] === $fingerprint) {
                return true;
            }
            
            // Check client-side fingerprint match as fallback
            if ($clientFingerprint && isset($device['client_fingerprint']) 
                && $device['client_fingerprint'] === $clientFingerprint) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a trusted device with optional client fingerprint and device info.
     */
    public function addTrustedDevice($fingerprint, $userAgent, $clientFingerprint = null, $deviceInfo = null)
    {
        $devices = $this->two_factor_device_fingerprints ?: [];
        
        // Remove existing entry for this fingerprint
        $devices = array_filter($devices, function($device) use ($fingerprint) {
            return $device['fingerprint'] !== $fingerprint;
        });

        // Build device entry
        $device = [
            'fingerprint' => $fingerprint,
            'user_agent' => $userAgent,
            'added_at' => now()->timestamp,
            'expires_at' => now()->addDays(90)->timestamp, // Extended to 90 days
        ];
        
        if ($clientFingerprint) {
            $device['client_fingerprint'] = $clientFingerprint;
        }
        
        if ($deviceInfo) {
            $device['device_info'] = $deviceInfo;
        }
        
        $devices[] = $device;

        // Keep only last 10 devices
        $devices = array_slice($devices, -10);

        $this->two_factor_device_fingerprints = array_values($devices);
        $this->save();
    }

    public function removeTrustedDevice($fingerprint)
    {
        if (!$this->two_factor_device_fingerprints) {
            return;
        }

        $devices = array_filter($this->two_factor_device_fingerprints, function($device) use ($fingerprint) {
            return $device['fingerprint'] !== $fingerprint;
        });

        $this->two_factor_device_fingerprints = array_values($devices);
        $this->save();
    }

    public function clearAllTrustedDevices()
    {
        $this->two_factor_device_fingerprints = [];
        $this->save();
    }

    /**
     * Get the has_two_factor_enabled attribute.
     *
     * @return bool
     */
    public function getHasTwoFactorEnabledAttribute()
    {
        return $this->hasTwoFactorEnabled();
    }
}
