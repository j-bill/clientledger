<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postcode',
        'country',
        'vat_number',
        'hourly_rate',
        'invoice_default_message',
        'invoice_payment_terms',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
    ];

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
