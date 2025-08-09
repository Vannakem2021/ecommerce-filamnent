<?php

namespace App\Console\Commands;

use App\Services\InventoryReservationService;
use Illuminate\Console\Command;

class CleanupExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:cleanup-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired inventory reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reservationService = new InventoryReservationService();

        $this->info('Cleaning up expired inventory reservations...');

        $cleanedCount = $reservationService->cleanupExpiredReservations();

        $this->info("Cleaned up {$cleanedCount} expired reservations.");

        return Command::SUCCESS;
    }
}
