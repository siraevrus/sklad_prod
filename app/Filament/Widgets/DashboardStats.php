<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Product;
use App\Models\Product as ProductModel;
use App\Models\Request;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Компаний', Company::where('is_archived', false)->count())
                ->description('Активные компании')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success')
                ->url('admin/companies'),

            Stat::make('Сотрудников', User::where('is_blocked', false)->count())
                ->description('Активные сотрудники')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->url('admin/users'),

            Stat::make('Складов', Warehouse::where('is_active', true)->count())
                ->description('Активные склады')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('warning')
                ->url('admin/warehouses'),

            Stat::make('Товаров', Product::count())
                ->description('Всего товаров')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success')
                ->url('admin/products'),

            Stat::make('В пути', ProductModel::where('status', ProductModel::STATUS_IN_TRANSIT)
                ->where('is_active', true)
                ->count())
                ->description('Товары в доставке')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning')
                ->url('admin/product-in-transits'),

            Stat::make('Запросы', Request::byStatus(Request::STATUS_PENDING)->count())
                ->description('Ожидают рассмотрения')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->url('admin/requests'),

            Stat::make('Последние продажи', Sale::query()
                ->where('payment_status', '!=', Sale::PAYMENT_STATUS_CANCELLED)
                ->count())
                ->description('Всего продаж')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->url('admin/sales'),
        ];
    }
}
