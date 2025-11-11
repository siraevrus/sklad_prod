<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'currency_rate',
    ];

    protected $casts = [
        'currency_rate' => 'decimal:4',
    ];

    /**
     * Связь с продажами
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
