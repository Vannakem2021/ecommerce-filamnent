<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OrderStats extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->can('dashboard.view');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('New Orders', Order::query()->where('status', 'new')->count())
                ->description('Recently placed orders')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),

            Stat::make('Delivered Orders', Order::query()->where('status', 'delivered')->count())
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Orders under Processing', Order::query()->where('status', 'processing')->count())
                ->description('Currently being processed')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make('Shipped Orders', Order::query()->where('status', 'shipped')->count())
                ->description('On the way to customers')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Cancelled Orders', Order::query()->where('status', 'cancelled')->count())
                ->description('Orders that were cancelled')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Average Price', Number::currency(Order::query()->avg('grand_total') ?? 0, 'INR'))
                ->description('Average order value')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('gray'),

        ];
    }
}
