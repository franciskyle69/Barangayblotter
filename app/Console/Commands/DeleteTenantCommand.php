<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete {slug? : The tenant slug to delete} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a tenant (barangay) and all associated data (incidents, users, mediations, patrol logs, blotter requests)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the tenant slug
        $slug = $this->argument('slug');
        
        if (!$slug) {
            // Show list of tenants if no slug provided
            $this->displayTenantsList();
            $slug = $this->ask('Enter the slug of the tenant to delete');
        }

        // Find the tenant
        $tenant = Tenant::where('slug', $slug)->orWhere('id', $slug)->first();

        if (!$tenant) {
            $this->error("Tenant with slug or ID '{$slug}' not found.");
            return self::FAILURE;
        }

        // Display tenant information
        $this->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("TENANT DELETION WARNING");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->line("Tenant Information:");
        $this->line("  ID: <comment>{$tenant->id}</comment>");
        $this->line("  Name: <comment>{$tenant->name}</comment>");
        $this->line("  Slug: <comment>{$tenant->slug}</comment>");
        $this->line("  Barangay: <comment>{$tenant->barangay}</comment>");
        $this->line("  Status: <comment>" . ($tenant->is_active ? 'Active' : 'Inactive') . "</comment>");
        $this->newLine();

        // Count related data
        $incidentCount = $tenant->incidents()->count();
        $userCount = $tenant->users()->count();
        $mediationCount = $tenant->mediations()->count();
        $patrolLogCount = $tenant->patrolLogs()->count();
        $blotterRequestCount = $tenant->blotterRequests()->count();

        $this->warn("This action will DELETE the following data:");
        $this->line("  • <fg=red>{$incidentCount}</> Incident(s)");
        $this->line("  • <fg=red>{$mediationCount}</> Mediation(s)");
        $this->line("  • <fg=red>{$patrolLogCount}</> Patrol Log(s)");
        $this->line("  • <fg=red>{$blotterRequestCount}</> Blotter Request(s)");
        $this->line("  • <fg=red>{$userCount}</> User Association(s)");
        $this->newLine();

        $this->line("This action is <fg=red>PERMANENT</> and <fg=red>CANNOT BE UNDONE</>");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        // Confirmation prompt
        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to delete this tenant and all its data?")) {
                $this->line('<fg=yellow>Deletion cancelled.</>');
                return self::SUCCESS;
            }

            // Double confirmation
            $confirmName = $this->ask('Type the tenant name "<comment>' . $tenant->name . '</comment>" to confirm');
            if ($confirmName !== $tenant->name) {
                $this->error('Confirmation name does not match. Deletion cancelled.');
                return self::FAILURE;
            }
        }

        // Delete the tenant and all related data
        try {
            $this->info('Deleting tenant and all associated data...');
            
            DB::transaction(function () use ($tenant) {
                // Delete related data in order (respecting foreign keys if any)
                $tenant->incidents()->delete();
                $this->line('  ✓ Deleted incidents');

                $tenant->mediations()->delete();
                $this->line('  ✓ Deleted mediations');

                $tenant->patrolLogs()->delete();
                $this->line('  ✓ Deleted patrol logs');

                $tenant->blotterRequests()->delete();
                $this->line('  ✓ Deleted blotter requests');

                // Detach all users
                $tenant->users()->detach();
                $this->line('  ✓ Removed user associations');

                // Delete the tenant
                $tenant->delete();
                $this->line('  ✓ Deleted tenant');
            });

            $this->info("\n✅ Tenant '<comment>{$tenant->name}</comment>' has been successfully deleted!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error deleting tenant: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display a list of all tenants
     */
    protected function displayTenantsList(): void
    {
        $tenants = Tenant::select(['id', 'name', 'slug', 'barangay', 'is_active'])
            ->orderBy('name')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return;
        }

        $this->info("\n📋 Available Tenants:");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->table(
            ['ID', 'Name', 'Slug', 'Barangay', 'Status'],
            $tenants->map(fn($t) => [
                $t->id,
                $t->name,
                $t->slug,
                $t->barangay,
                $t->is_active ? '✓ Active' : '✗ Inactive'
            ])->toArray()
        );

        $this->newLine();
    }
}
