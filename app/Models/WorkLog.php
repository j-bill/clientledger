<?php

// WorkLog.php

namespace App\Models;

use Database\Factories\WorkLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class WorkLog extends Model
{
    /** @use HasFactory<WorkLogFactory> */
    use HasFactory;

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

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasOneThrough<Customer, Project, $this>
     */
    public function customer(): HasOneThrough
    {
        return $this->hasOneThrough(Customer::class, Project::class, 'id', 'id', 'project_id', 'customer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Invoice, $this>
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class)->withTimestamps();
    }

    /**
     * The amount owed to the user (hours worked at the user's rate).
     */
    public function getAmountAttribute(): float
    {
        if ($this->hours_worked && $this->user_hourly_rate) {
            return (float) $this->hours_worked * (float) $this->user_hourly_rate;
        }

        return 0.0;
    }

    /**
     * The billing rate (what the customer pays), falling back to the
     * project rate and then the customer rate when none is stored.
     */
    public function getBillingRateAttribute(): float
    {
        if ($this->hourly_rate) {
            return (float) $this->hourly_rate;
        }

        if ($this->project?->hourly_rate) {
            return (float) $this->project->hourly_rate;
        }

        if ($this->customer?->hourly_rate) {
            return (float) $this->customer->hourly_rate;
        }

        return 0.0;
    }

    /**
     * The billing amount (what the customer pays).
     */
    public function getBillingAmountAttribute(): float
    {
        if ($this->hours_worked) {
            return (float) $this->hours_worked * $this->billing_rate;
        }

        return 0.0;
    }

    /**
     * The first work log of this user on this date whose time range clashes
     * with the given range.
     *
     * Ranges are half-open: a log ending at 12:00 does not clash with one
     * starting at 12:00. A missing end time counts as running until the end
     * of the day, because that time is unaccounted for.
     */
    public static function findClash(int $userId, string $date, string $startTime, ?string $endTime, ?int $ignoreId = null): ?self
    {
        $start = self::minutesOfDay($startTime);
        $end = $endTime === null ? 1440 : self::minutesOfDay($endTime);

        $query = static::query()
            ->where('user_id', $userId)
            ->whereDate('date', substr($date, 0, 10))
            ->whereNotNull('start_time');

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        foreach ($query->with('project')->get() as $existing) {
            $existingStart = self::minutesOfDay($existing->start_time?->format('H:i') ?? '00:00');
            $existingEnd = $existing->end_time === null ? 1440 : self::minutesOfDay($existing->end_time->format('H:i'));

            if ($start < $existingEnd && $existingStart < $end) {
                return $existing;
            }
        }

        return null;
    }

    /**
     * Minutes since midnight for an "H:i" (or "H:i:s") time string.
     */
    private static function minutesOfDay(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) $parts[0]) * 60 + (int) ($parts[1] ?? 0);
    }

    /**
     * Scope for freelancers to only see their own work logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
