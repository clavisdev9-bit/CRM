<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class LeadsImport implements ToCollection
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $insertData = [];
        $userId = $this->userId;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // skip header
            if (empty($row[0]) || empty($row[1])) continue; // skip jika company/contact kosong

            // Cek duplicate di DB
            $exists = DB::table('leads')
                ->where('company_name', $row[0])
                ->where('contact_name', $row[1])
                ->exists();

            if ($exists) continue;

            $insertData[] = [
                'company_name'      => $row[0],
                'contact_name'      => $row[1],
                'email'             => $row[2] ?? null,
                'phone'             => $row[3] ?? null,
                'lead_source'       => $row[4] ?? null,
                'lead_status'       => $row[5] ?? 'New',
                'industry_id'       => $row[6] ?? null,
                'lead_category_id'  => $row[7] ?? null,
                'assigned_to'       => $row[8] ?? null,
                'id_user'           => $userId,
                'created_by'        => $userId,
                'visibility_type'   => $row[9] ?? 'PRIVATE',
                'notes'             => $row[10] ?? null,
                'last_contacted_at' => $row[11] ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        if (!empty($insertData)) {
            DB::table('leads')->insert($insertData); // batch insert
        }
    }
}
