<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil lead yang sudah converted
        $leads = DB::table('leads')
            ->where('lead_status', 'Converted')
            ->get();

        foreach ($leads as $lead) {

            // Cegah duplicate customer
            $exists = DB::table('customers')
                ->where('lead_id', $lead->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('customers')->insert([
                'lead_id' => $lead->id,
                'lead_category_id' => $lead->lead_category_id,
                'industry_id' => $lead->industry_id,
                'lead_source' => $lead->lead_source,

                'customer_code' => $this->generateCustomerCode(),
                'company_name' => $lead->company_name,
                'contact_name' => $lead->contact_name,
                'email' => $lead->email,
                'phone' => $lead->phone,

                'id_user' => $lead->id_user,
                'assigned_to' => $lead->assigned_to,
                'created_by' => $lead->created_by,
                'visibility_type' => 'PRIVATE',

                'customer_status' => 'Converted',
                'address' => $lead->address,
                'notes' => $lead->notes,

                'converted_at' => $lead->converted_at ?? Carbon::now(),

                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    private function generateCustomerCode(): string
    {
        $lastId = DB::table('customers')->max('id') ?? 0;
        return 'CUST-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
