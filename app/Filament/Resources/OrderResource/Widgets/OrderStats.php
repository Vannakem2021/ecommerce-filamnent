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
            Stat::make('New Orders', Order::query()->where('status', 'new')->count()),

            Stat::make('Delivered Orders', Order::query()->where('status', 'delivered')->count()),

            Stat::make('Orders under Processing', Order::query()->where('status', 'processing')->count()),

            Stat::make('Shipped Orders', Order::query()->where('status', 'shipped')->count()),

            Stat::make('Cancelled Orders', Order::query()->where('status', 'cancelled')->count()),

            Stat::make('Average Price', Number::currency(Order::query()->avg('grand_total') ?? 0, 'INR')),

        ];
    }
}
