<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FollowUpsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

      DB::table('follow_ups')->insert([
    [
        'follow_up_code' => 'FU-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
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
        'follow_up_code' => 'FU-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
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
        'follow_up_code' => 'FU-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
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
