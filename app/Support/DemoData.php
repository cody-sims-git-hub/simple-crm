<?php

namespace App\Support;

use App\Models\User;

class DemoData
{
    /**
     * Provision the starter CRM pipeline for a user so their dashboard,
     * pipeline and reporting views are populated from day one.
     */
    public static function provisionLeadsFor(User $user): void
    {
        foreach (self::leads() as $lead) {
            $user->leads()->create($lead);
        }
    }

    /**
     * The sample lead pipeline seeded into every new account.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function leads(): array
    {
        return [
            [
                'name' => 'Sarah Jenkins',
                'email' => 'sjenkins@example.com',
                'phone' => '404-555-0143',
                'insurance_type' => 'Health',
                'lead_score' => 90, // Baseline 50 + Phone (20) + Health (20)
                'priority' => 'High',
                'status' => 'New',
                'notes' => 'Inbound organic web lead. Highly interested in family health deductible coverage options.',
            ],
            [
                'name' => 'Michael Chang',
                'email' => 'mchang@example.com',
                'phone' => '650-555-0188',
                'insurance_type' => 'Life',
                'lead_score' => 70, // Baseline 50 + Phone (20) + Life (0)
                'priority' => 'Medium',
                'status' => 'Contacted',
                'notes' => 'Spoke briefly with consumer. Requested a follow-up quote regarding a 20-year term life policy.',
            ],
            [
                'name' => 'Robert Logan',
                'email' => 'rlogan@example.com',
                'phone' => '312-555-0122',
                'insurance_type' => 'Medicare',
                'lead_score' => 85, // Baseline 50 + Phone (20) + Medicare (15)
                'priority' => 'High',
                'status' => 'Quoted',
                'notes' => 'Turning 65 next month. Looking for immediate assistance navigating Medicare Advantage enrollments.',
            ],
            [
                'name' => 'Amanda Ross',
                'email' => 'aross@example.com',
                'phone' => '212-555-0195',
                'insurance_type' => 'Health',
                'lead_score' => 90,
                'priority' => 'High',
                'status' => 'Submitted',
                'notes' => 'Medical underwriting documentation successfully processed and uploaded to the centralized carrier queue.',
            ],
            [
                'name' => 'David Brooks',
                'email' => 'dbrooks@example.com',
                'phone' => '713-555-0167',
                'insurance_type' => 'Life',
                'lead_score' => 70,
                'priority' => 'Medium',
                'status' => 'Closed',
                'notes' => 'Policy issued and verified by carrier. Premium billing cycle active. Welcome kit dispatched.',
            ],
            [
                'name' => 'Elena Rostova',
                'email' => 'erostova@example.com',
                'phone' => null, // Testing contactability drop
                'insurance_type' => 'Health',
                'lead_score' => 70, // Baseline 50 + No Phone (0) + Health (20)
                'priority' => 'Medium',
                'status' => 'New',
                'notes' => 'Captured via digital affiliate link banner advertisement. Missing direct voice telecomm options.',
            ],
            [
                'name' => 'William Vance',
                'email' => 'wvance@example.com',
                'phone' => null,
                'insurance_type' => 'Life',
                'lead_score' => 50, // Baseline 50 + No Phone (0) + Life (0)
                'priority' => 'Low',
                'status' => 'New',
                'notes' => 'Cold email subscription opt-in capture. Lowest prioritization route configuration.',
            ],
            [
                'name' => 'James Patel',
                'email' => 'jpatel@example.com',
                'phone' => '202-555-0119',
                'insurance_type' => 'Medicare',
                'lead_score' => 85, // Baseline 50 + Phone (20) + Medicare (15)
                'priority' => 'High',
                'status' => 'Contacted',
                'notes' => 'Referred by existing policyholder. Comparing supplemental Medigap plans for spouse.',
            ],
            [
                'name' => 'Olivia Nguyen',
                'email' => 'onguyen@example.com',
                'phone' => '415-555-0174',
                'insurance_type' => 'Health',
                'lead_score' => 90, // Baseline 50 + Phone (20) + Health (20)
                'priority' => 'High',
                'status' => 'Quoted',
                'notes' => 'Comparing family PPO tiers. Requested a side-by-side deductible breakdown before deciding.',
            ],
            [
                'name' => 'Marcus Webb',
                'email' => 'mwebb@example.com',
                'phone' => null,
                'insurance_type' => 'Life',
                'lead_score' => 50, // Baseline 50 + No Phone (0) + Life (0)
                'priority' => 'Low',
                'status' => 'New',
                'notes' => 'Form abandoned at contact step. Only age band and coverage interest captured so far.',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'psharma@example.com',
                'phone' => '847-555-0136',
                'insurance_type' => 'Health',
                'lead_score' => 90, // Baseline 50 + Phone (20) + Health (20)
                'priority' => 'High',
                'status' => 'Closed',
                'notes' => 'Gold-tier PPO finalized during open enrollment. Auto-pay enrolled and ID cards issued.',
            ],
            [
                'name' => 'Daniel Okafor',
                'email' => 'dokafor@example.com',
                'phone' => null,
                'insurance_type' => 'Medicare',
                'lead_score' => 65, // Baseline 50 + No Phone (0) + Medicare (15)
                'priority' => 'Medium',
                'status' => 'Submitted',
                'notes' => 'Enrollment application submitted to carrier; awaiting CMS confirmation. Reachable by email only.',
            ],
            [
                'name' => 'Sofia Romano',
                'email' => 'sromano@example.com',
                'phone' => '305-555-0188',
                'insurance_type' => 'Life',
                'lead_score' => 70, // Baseline 50 + Phone (20) + Life (0)
                'priority' => 'Medium',
                'status' => 'Contacted',
                'notes' => 'Interested in whole-life with cash value. Scheduling a needs-analysis call next week.',
            ],
            [
                'name' => 'Ethan Cole',
                'email' => 'ecole@example.com',
                'phone' => '503-555-0151',
                'insurance_type' => 'Medicare',
                'lead_score' => 85, // Baseline 50 + Phone (20) + Medicare (15)
                'priority' => 'High',
                'status' => 'Submitted',
                'notes' => 'Aging into Medicare; switching from employer plan. Enrollment paperwork submitted to carrier.',
            ],
            [
                'name' => 'Grace Kim',
                'email' => 'gkim@example.com',
                'phone' => null,
                'insurance_type' => 'Health',
                'lead_score' => 70, // Baseline 50 + No Phone (0) + Health (20)
                'priority' => 'Medium',
                'status' => 'Quoted',
                'notes' => 'Self-employed; evaluating Silver vs Bronze marketplace plans. Prefers email correspondence.',
            ],
        ];
    }
}
