<?php

// Project.php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
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

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_project')
            ->withPivot('hourly_rate')
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkLog, $this>
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    /**
     * @return float|int|string|null
     */
    public function getHourlyRateForUser(User $user)
    {
        $pivotRate = $this->users()
            ->where('user_id', $user->id)
            ->value('user_project.hourly_rate');

        if (is_float($pivotRate) || is_int($pivotRate) || is_string($pivotRate)) {
            return $pivotRate;
        }

        return $this->hourly_rate;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
