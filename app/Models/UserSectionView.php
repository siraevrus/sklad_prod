<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSectionView extends Model
{
    public const SECTION_RECEIPTS = 'receipts';

    public const SECTION_PRODUCTS_IN_TRANSIT = 'products_in_transit';

    public const SECTION_SALES = 'sales';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'section',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
