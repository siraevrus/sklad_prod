<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'composite_product_key',
        'warehouse_id',
        'user_id',
        'sale_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'quantity',
        'unit_price',
        'total_price',
        'cash_amount',
        'nocash_amount',
        'price_without_vat',
        'currency',
        'exchange_rate',
        'payment_method',
        'payment_status',
        'notes',
        'invoice_number',
        'sale_date',
        'is_active',
        'reason_cancellation',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'nocash_amount' => 'decimal:2',
        'price_without_vat' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'sale_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Статусы оплаты
    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_PARTIALLY_PAID = 'partially_paid';

    const PAYMENT_STATUS_CANCELLED = 'cancelled';

    /**
     * Связь с товаром
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Связь со складом
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Связь с пользователем, оформившим продажу
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с компанией через склад
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'warehouse_id', 'id');
    }

    /**
     * Генерировать номер продажи с защитой от дубликатов
     */
    public static function generateSaleNumber(): string
    {
        $prefix = 'SALE';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Используем комбинацию: микросекунды + случайное число для уникальности
        $microtime = (string) microtime(true);
        $uniquePart = str_replace('.', '', $microtime);
        $uniquePart = substr($uniquePart, -8); // Берем последние 8 символов

        $saleNumber = sprintf('%s-%s%s-%s', $prefix, $year, $month, $uniquePart);

        // На случай если вдруг совпадет, пробуем с добавлением случайного числа
        if (static::where('sale_number', $saleNumber)->exists()) {
            $random = random_int(1000, 9999);
            $saleNumber = sprintf('%s-%s%s-%s%d', $prefix, $year, $month, $uniquePart, $random);
        }

        return $saleNumber;
    }

    /**
     * Рассчитать цены
     */
    public function calculatePrices(): void
    {
        $this->price_without_vat = $this->unit_price * $this->quantity;
        $this->total_price = $this->price_without_vat;
    }

    /**
     * Проверить, можно ли оформить продажу
     */
    public function canBeSold(): bool
    {
        // Доступность продажи определяется наличием достаточного количества товара
        return $this->product && $this->product->getAvailableQuantity() >= $this->quantity;
    }

    /**
     * Оформить продажу (списать товар со склада)
     */
    public function processSale(): bool
    {
        if (! $this->canBeSold()) {
            return false;
        }

        // Списываем товар со склада
        $success = $this->product->decreaseQuantity($this->quantity);

        if ($success) {
            $this->payment_status = self::PAYMENT_STATUS_PAID;
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Отменить продажу (вернуть товар на склад)
     */
    public function cancelSale(?string $reason = null): bool
    {
        if ($this->payment_status === self::PAYMENT_STATUS_CANCELLED) {
            return false; // Уже отменена
        }

        // Возвращаем товар на склад
        $this->product->increaseQuantity($this->quantity);

        $this->payment_status = self::PAYMENT_STATUS_CANCELLED;
        $this->reason_cancellation = $reason;
        $this->save();

        return true;
    }

    /**
     * Получить статус оплаты на русском языке
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
            self::PAYMENT_STATUS_PAID => 'Оплачено',
            self::PAYMENT_STATUS_PARTIALLY_PAID => 'Частично оплачено',
            self::PAYMENT_STATUS_CANCELLED => 'Отменено',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить цвет статуса оплаты
     */
    public function getPaymentStatusColor(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'warning',
            self::PAYMENT_STATUS_PAID => 'success',
            self::PAYMENT_STATUS_PARTIALLY_PAID => 'info',
            self::PAYMENT_STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Получить полное имя клиента
     */
    public function getCustomerFullName(): string
    {
        if ($this->customer_name) {
            return $this->customer_name;
        }

        return 'Клиент не указан';
    }

    /**
     * Получить контактную информацию клиента
     */
    public function getCustomerContact(): string
    {
        $contacts = [];

        if ($this->customer_phone) {
            $contacts[] = $this->customer_phone;
        }

        if ($this->customer_email) {
            $contacts[] = $this->customer_email;
        }

        return $contacts ? implode(', ', $contacts) : 'Контакты не указаны';
    }

    /**
     * Scope для активных продаж
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope для фильтрации по статусу оплаты
     */
    public function scopeByPaymentStatus(Builder $query, string $status): void
    {
        $query->where('payment_status', $status);
    }

    /**
     * Scope для фильтрации по складу
     */
    public function scopeByWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope для фильтрации по пользователю
     */
    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope для фильтрации по дате продажи
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope для оплаченных продаж
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    /**
     * Получить статистику по продажам
     */
    public static function getStats(): array
    {
        return [
            'total_sales' => static::count(),
            'paid_sales' => static::paid()->count(),
            'pending_payments' => static::byPaymentStatus(self::PAYMENT_STATUS_PENDING)->count(),
            'total_revenue' => static::paid()->sum('total_price'),
            'total_quantity' => static::sum('quantity'),
        ];
    }

    /**
     * Получить варианты статусов оплаты для фильтра
     */
    public static function getPaymentStatusOptions(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
            self::PAYMENT_STATUS_PAID => 'Оплачено',
            self::PAYMENT_STATUS_PARTIALLY_PAID => 'Частично оплачено',
            self::PAYMENT_STATUS_CANCELLED => 'Отменено',
        ];
    }
}
