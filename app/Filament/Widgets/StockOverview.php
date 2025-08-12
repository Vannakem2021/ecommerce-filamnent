<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get basic stock statistics
        $totalProducts = Product::where('is_active', true)->count();
        
        // Count products by stock status
        $inStockCount = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        
        // Get all active products with their variants
        $products = Product::where('is_active', true)
            ->with(['variants' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();
            
        foreach ($products as $product) {
            $status = InventoryService::getStockStatus($product);
            
            switch ($status) {
                case 'in_stock':
                    $inStockCount++;
                    break;
                case 'low_stock':
                    $lowStockCount++;
                    break;
                case 'out_of_stock':
                    $outOfStockCount++;
                    break;
            }
        }
        
        // Calculate total stock value (simplified)
        $totalStockValue = 0;
        foreach ($products as $product) {
            $stock = InventoryService::getTotalStock($product);
            $price = $product->price ?? 0;
            $totalStockValue += $stock * $price;
        }

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Active products in catalog')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('In Stock', $inStockCount)
                ->description('Products available')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Low Stock', $lowStockCount)
                ->description('Products running low')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
                
            Stat::make('Out of Stock', $outOfStockCount)
                ->description('Products unavailable')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}
