<?php

namespace App\Http\Requests;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ── Validasi create Notification, untuk category = bulk & reminder
 * (Phase A). category = agenda belum dibuka di sini -- menyusul Phase B
 * begitu modul Agenda existing sudah di-link (lihat catatan di
 * NotificationController::store()).
 *
 * `target` dipakai untuk nentuin siapa penerimanya, berlaku untuk:
 *   - category = bulk (selalu butuh target)
 *   - category = reminder DENGAN reminder_scope = broadcast
 * target = 'all'      -> semua user aktif jadi penerima
 * target = 'specific' -> penerima dipilih manual lewat recipient_ids
 *
 * reminder_scope = personal TIDAK butuh target/recipient_ids sama
 * sekali -- controller yang otomatis set penerimanya ke user pembuat
 * sendiri (Auth::user()->id_user).
 * ──
 */
class NotificationValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category      = $this->input('category');
        $isBulk        = $category === 'bulk';
        $isReminder    = $category === 'reminder';
        $reminderType  = $this->input('reminder_type');
        $reminderScope = $this->input('reminder_scope');

        // ── target/recipient_ids wajib untuk bulk, atau reminder yang
        // scope-nya broadcast. Reminder personal tidak butuh ini sama
        // sekali (penerima = diri sendiri, di-set controller). ──
        $needsTarget = $isBulk || ($isReminder && $reminderScope === 'broadcast');
        $target      = $this->input('target');

        return [
            // ── karena Phase A belum buka category=agenda, in: sengaja
            // dibatasi ke bulk,reminder dulu (bukan pakai
            // Notification::CATEGORIES langsung yang sudah termasuk
            // 'agenda'). ──
            'category' => ['required', 'string', 'in:bulk,reminder'],

            'title'   => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],

            'target' => [$needsTarget ? 'required' : 'nullable', 'nullable', 'string', 'in:all,specific'],

            'recipient_ids'   => [$needsTarget && $target === 'specific' ? 'required' : 'nullable', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', Rule::exists('ms_users', 'id_user')],

            // ── kolom khusus reminder ──
            'reminder_type'  => [$isReminder ? 'required' : 'nullable', 'nullable', 'string', 'in:' . implode(',', Notification::REMINDER_TYPES)],
            'reminder_scope' => [$isReminder ? 'required' : 'nullable', 'nullable', 'string', 'in:' . implode(',', Notification::REMINDER_SCOPES)],

            'day_of_week'  => [$reminderType === 'weekly' ? 'required' : 'nullable', 'nullable', 'integer', 'between:0,6'],
            'day_of_month' => [$reminderType === 'monthly' ? 'required' : 'nullable', 'nullable', 'integer', 'between:1,31'],

            'time_of_day' => [
                in_array($reminderType, ['daily', 'weekly', 'monthly'], true) ? 'required' : 'nullable',
                'nullable', 'date_format:H:i',
            ],

            // ── bulk: opsional (kalau mau dijadwalkan, bukan kirim
            // langsung). reminder (scheduled): wajib -- ini kapan
            // reminder itu fire, sekali saja. ──
            'scheduled_at' => [
                $reminderType === 'scheduled' ? 'required' : 'nullable',
                'nullable', 'date', 'after_or_equal:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required'        => 'Kategori notifikasi wajib dipilih.',
            'category.in'               => 'Kategori tidak valid.',
            'title.required'            => 'Judul notifikasi wajib diisi.',
            'message.required'          => 'Isi pesan wajib diisi.',
            'target.required'           => 'Target penerima wajib dipilih.',
            'recipient_ids.required'    => 'Penerima wajib dipilih untuk target "specific".',
            'reminder_type.required'    => 'Tipe reminder wajib dipilih.',
            'reminder_scope.required'   => 'Scope reminder wajib dipilih.',
            'day_of_week.required'      => 'Hari wajib dipilih untuk reminder mingguan.',
            'day_of_month.required'     => 'Tanggal wajib dipilih untuk reminder bulanan.',
            'time_of_day.required'      => 'Jam eksekusi wajib diisi.',
            'scheduled_at.required'     => 'Tanggal & jam wajib diisi untuk reminder terjadwal.',
            'scheduled_at.after_or_equal' => 'Tanggal & jam harus di masa depan.',
        ];
    }
}