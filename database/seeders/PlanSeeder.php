<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'incident_limit_per_month' => 200,
                'online_complaint_submission' => false,
                'mediation_scheduling' => false,
                'sms_status_updates' => false,
                'analytics_dashboard' => true,
                'auto_case_number' => false,
                'qr_verification' => false,
                'central_monitoring' => false,
                'price_monthly' => 0,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'incident_limit_per_month' => 2000,
                'online_complaint_submission' => true,
                'mediation_scheduling' => true,
                'sms_status_updates' => true,
                'analytics_dashboard' => true,
                'auto_case_number' => false,
                'qr_verification' => false,
                'central_monitoring' => false,
                'price_monthly' => 0,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'incident_limit_per_month' => null,
                'online_complaint_submission' => true,
                'mediation_scheduling' => true,
                'sms_status_updates' => true,
                'analytics_dashboard' => true,
                'auto_case_number' => true,
                'qr_verification' => true,
                'central_monitoring' => true,
                'price_monthly' => 0,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
