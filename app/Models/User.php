<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verification_code',
        'two_factor_device_fingerprints',
        // Avatars are base64 data URIs and can be ~1 MB. Serializing them on every
        // nested user relation blows the response size up, so they are opt-in via
        // makeVisible() on the endpoints that actually render the avatar.
        'avatar',
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
     * @var list<string>
     */
    protected $appends = ['has_two_factor_enabled'];

    // Relationships

    /**
     * @return HasMany<WorkLog, $this>
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_project')
            ->withPivot('hourly_rate')
            ->withTimestamps();
    }

    // Role-based access control methods
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isFreelancer(): bool
    {
        return $this->hasRole('freelancer');
    }

    /**
     * Scope for freelancers to only see their own data.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFreelancer(Builder $query): Builder
    {
        return $query->where('role', 'freelancer');
    }

    /**
     * Get hourly rate for a specific project.
     *
     * @return float|int|string|null
     */
    public function getProjectHourlyRate(Project $project)
    {
        $pivotRate = $this->projects()
            ->where('project_id', $project->id)
            ->value('user_project.hourly_rate');

        if (is_float($pivotRate) || is_int($pivotRate) || is_string($pivotRate)) {
            return $pivotRate;
        }

        return $this->hourly_rate;
    }

    /**
     * Calculate total earnings for a period.
     *
     * @param  \DateTimeInterface|string  $startDate
     * @param  \DateTimeInterface|string  $endDate
     */
    public function calculateEarnings($startDate, $endDate): float
    {
        return (float) $this->workLogs()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum(DB::raw('hours_worked * user_hourly_rate'));
    }

    // 2FA Methods
    public function twoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Serialized as has_two_factor_enabled (see $appends).
     *
     * @return Attribute<bool, never>
     */
    protected function hasTwoFactorEnabled(): Attribute
    {
        return Attribute::get(fn (): bool => $this->twoFactorEnabled());
    }

    /**
     * Check if a device is trusted by fingerprint or client fingerprint.
     */
    public function isDeviceTrusted(?string $fingerprint, ?string $clientFingerprint = null): bool
    {
        if (! $this->two_factor_device_fingerprints) {
            return false;
        }

        $now = now()->timestamp;

        foreach ($this->two_factor_device_fingerprints as $device) {
            if (! is_array($device)) {
                continue;
            }

            // Skip expired devices
            $expiresAt = $device['expires_at'] ?? null;
            if (! is_numeric($expiresAt) || $expiresAt <= $now) {
                continue;
            }

            // Check server-side fingerprint match
            if (($device['fingerprint'] ?? null) === $fingerprint) {
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
     *
     * @param  array<string, mixed>|null  $deviceInfo
     */
    public function addTrustedDevice(string $fingerprint, ?string $userAgent, ?string $clientFingerprint = null, ?array $deviceInfo = null): void
    {
        $devices = $this->two_factor_device_fingerprints ?: [];

        // Remove existing entry for this fingerprint
        $devices = array_filter($devices, function ($device) use ($fingerprint) {
            return ! is_array($device) || ($device['fingerprint'] ?? null) !== $fingerprint;
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

    public function removeTrustedDevice(string $fingerprint): void
    {
        if (! $this->two_factor_device_fingerprints) {
            return;
        }

        $devices = array_filter($this->two_factor_device_fingerprints, function ($device) use ($fingerprint) {
            return ! is_array($device) || ($device['fingerprint'] ?? null) !== $fingerprint;
        });

        $this->two_factor_device_fingerprints = array_values($devices);
        $this->save();
    }

    public function clearAllTrustedDevices(): void
    {
        $this->two_factor_device_fingerprints = [];
        $this->save();
    }
}
