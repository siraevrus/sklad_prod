<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'middle_name',
        'phone',
        'role',
        'company_id',
        'warehouse_id',
        'is_blocked',
        'blocked_at',
        'last_app_opened_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Casts\UserRoleCast::class,
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'last_app_opened_at' => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->last_name,
            $this->first_name,
            $this->middle_name,
        ]);

        return implode(' ', $parts) ?: $this->name;
    }

    /**
     * Get the user's name for API responses.
     * This accessor ensures the 'name' field is always available in JSON responses.
     */
    public function getNameAttribute($value): string
    {
        // Если поле name заполнено в БД, возвращаем его
        if (! empty($value)) {
            return $value;
        }

        // Иначе формируем из first_name + last_name
        $parts = array_filter([
            $this->attributes['first_name'] ?? null,
            $this->attributes['last_name'] ?? null,
        ]);

        $generatedName = implode(' ', $parts);

        // Если и это пустое, возвращаем username или email
        if (empty($generatedName)) {
            return $this->attributes['username'] ?? $this->attributes['email'] ?? 'Пользователь';
        }

        return trim($generatedName);
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->role->permissions());
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is blocked.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the warehouse that owns the user.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Check if user can access Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Все аутентифицированные пользователи могут получить доступ к админке
        return ! $this->isBlocked();
    }

    /**
     * Обновить время последнего открытия приложения
     */
    public function markAppOpened(): void
    {
        $this->update(['last_app_opened_at' => now()]);
    }

    /**
     * Получить время последнего открытия приложения
     */
    public function getLastAppOpenedAt(): ?\Carbon\Carbon
    {
        return $this->last_app_opened_at;
    }
}
