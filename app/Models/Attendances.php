<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendances extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'attendances';

    // Primary key
    protected $primaryKey = 'id';

    // Karena id di gambar adalah auto-increment
    public $incrementing = true;

    // Mengaktifkan created_at dan updated_at secara otomatis
    public $timestamps = true;

    /**
     * Kolom yang boleh diisi secara mass-assignment.
     * Sesuaikan dengan kolom yang ada di screenshot database kamu.
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'attendance_type',
        'attendance_datetime',
        'attendance_date',
        'attendance_time',
        'photo_path',
        'latitude',
        'longitude',
        'accuracy',
        'location_name',
        'accuracy_status',
        'policy_status',
        'policy_reason',
        'office_latitude',
        'office_longitude',
        'distance_from_office',
        'allowed_radius',
        'device_type',
        'ip_address',
        'attendance_status',
    ];

    /**
     * Jika ingin casting tipe data secara otomatis
     * (Opsional, tapi sangat disarankan untuk tanggal)
     */
    protected $casts = [
        'attendance_datetime' => 'datetime',
        'attendance_date' => 'date',
        'distance_from_office' => 'double',
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(MsUsers::class, 'user_id', 'id_user');
    }

    public function employee()
    {
        return $this->belongsTo(MsEmployee::class, 'employee_id', 'id_employee');
    }
}