<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DynamicPricingDemoSeeder;

class SetupDynamicPricingDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:dynamic-pricing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up a demo product with dynamic pricing to test the new pricing system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up dynamic pricing demo...');
        
        $seeder = new DynamicPricingDemoSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->newLine();
        $this->info('✅ Dynamic pricing demo setup complete!');
        $this->info('🔗 Visit your product page to see dynamic pricing in action.');
        $this->info('💡 Try selecting different storage and color options to see prices update in real-time.');
        
        return Command::SUCCESS;
    }
}
