<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteAllTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete-all {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all tenants and their associated data (use with caution!)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Tenant::count();

        if ($count === 0) {
            $this->info('No tenants found to delete.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  WARNING: You are about to delete ALL {$count} tenants and their data!");
        $this->warn('This action is PERMANENT and CANNOT BE UNDONE.');
        $this->line('');
        $this->warn('This will delete:');
        $this->warn('  • ' . Tenant::withCount('incidents')->get()->sum('incidents_count') . ' incidents');
        $this->warn('  • ' . Tenant::withCount('mediations')->get()->sum('mediations_count') . ' mediations');
        $this->warn('  • ' . Tenant::withCount('patrolLogs')->get()->sum('patrol_logs_count') . ' patrol logs');
        $this->warn('  • ' . Tenant::withCount('blotterRequests')->get()->sum('blotter_requests_count') . ' blotter requests');
        $this->line('');

        if (!$this->option('force')) {
            if (!$this->confirm('Are you absolutely sure you want to delete ALL tenants?', false)) {
                $this->line('<fg=yellow>Deletion cancelled.</>');
                return self::SUCCESS;
            }

            if (!$this->confirm('Type YES to confirm (this cannot be undone)', false)) {
                $this->line('<fg=yellow>Deletion cancelled.</>');
                return self::SUCCESS;
            }
        }

        try {
            DB::transaction(function () {
                $tenants = Tenant::all();
                
                foreach ($tenants as $tenant) {
                    // Delete all related data
                    $tenant->incidents()->delete();
                    $tenant->mediations()->delete();
                    $tenant->patrolLogs()->delete();
                    $tenant->blotterRequests()->delete();
                    $tenant->users()->detach();
                    $tenant->delete();
                }
            });

            $this->info("✅ All {$count} tenants have been successfully deleted!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error deleting tenants: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
