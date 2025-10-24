# Скрыта кнопка "Экспорт в Excel" в разделе "Приемка товаров"

## Изменения

Скрыта кнопка "Экспорт в Excel" в разделе "Приемка товаров" (`/admin/receipts`).

### Что изменено:

**Файл**: `app/Filament/Resources/ReceiptResource/Pages/ListReceipts.php`
**Метод**: `getHeaderActions()`

```php
// До изменения
protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('export')
            ->label('Экспорт в Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(route('receipts.export'))
            ->openUrlInNewTab(false),
    ];
}

// После изменения
protected function getHeaderActions(): array
{
    return [
        // Кнопка "Экспорт в Excel" скрыта
    ];
}
```

## Результат

### До изменения:
- ✅ Кнопка "Экспорт в Excel" отображалась в заголовке раздела

### После изменения:
- ❌ Кнопка "Экспорт в Excel" скрыта
- ✅ Функциональность экспорта сохранена (маршрут `/receipts/export` работает)
- ✅ Кнопка просто не отображается пользователям

## Развёртывание

```bash
ssh my
cd /var/www/sklad
git pull origin main
php artisan filament:optimize-clear
php artisan view:clear
```

## Проверка

После развёртывания:

1. **Перейти в раздел "Приемка товаров"** (`/admin/receipts`)
2. **Проверить заголовок** - кнопка "Экспорт в Excel" не должна отображаться
3. **Прямой доступ** к `/receipts/export` все еще работает (если нужен)

## Файлы изменены:
- `app/Filament/Resources/ReceiptResource/Pages/ListReceipts.php`

## Статус:
✅ **Скрыто** - кнопка "Экспорт в Excel" больше не отображается в разделе "Приемка товаров"
