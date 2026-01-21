<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FollowUpsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('follow_ups')->insert([
            [
                // FOLLOW UP LEAD
                'lead_id'        => 1,
                'customer_id'    => null,
                'follow_up_type' => 'CALL',
                'subject'        => 'Initial Contact',
                'notes'          => 'Call the lead to introduce our product.',
                'follow_up_at'   => $now->addDays(1),
                'status'         => 'PENDING',
                'assigned_to'    => 1,
                'created_by'     => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                // FOLLOW UP CUSTOMER
                'lead_id'        => null,
                'customer_id'    => 1,
                'follow_up_type' => 'EMAIL',
                'subject'        => 'Send Proposal',
                'notes'          => 'Send proposal document via email.',
                'follow_up_at'   => $now->addDays(2),
                'status'         => 'PENDING',
                'assigned_to'    => 1,
                'created_by'     => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                // DONE FOLLOW UP
                'lead_id'        => null,
                'customer_id'    => 1,
                'follow_up_type' => 'MEETING',
                'subject'        => 'Product Demo',
                'notes'          => 'Meeting completed successfully.',
                'follow_up_at'   => $now->subDays(1),
                'status'         => 'DONE',
                'assigned_to'    => 1,
                'created_by'     => 1,
                'created_at'     => $now->subDays(2),
                'updated_at'     => $now->subDays(1),
            ],
        ]);
    }
}
