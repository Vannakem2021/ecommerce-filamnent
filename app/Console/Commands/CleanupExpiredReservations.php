<?php

namespace App\Console\Commands;

use App\Models\InventoryReservation;
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
        $this->info('Cleaning up expired inventory reservations...');

        $deletedCount = InventoryReservation::cleanupExpired();

        $this->info("Cleaned up {$deletedCount} expired reservations.");

        return Command::SUCCESS;
    }
}
