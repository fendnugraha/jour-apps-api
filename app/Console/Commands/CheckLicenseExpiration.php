<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\License;
use Illuminate\Console\Command;

class CheckLicenseExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-license-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $licenses = License::where('is_active', true)
            ->where('period_end', '<', $today)
            ->where('is_paid', false)
            ->get();

        foreach ($licenses as $license) {
            $license->update([
                'is_active' => false,
                'last_checked_at' => now(),
            ]);

            $this->info("License expired: {$license->client_name}");
        }

        return Command::SUCCESS;
    }
}
