<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi query params untuk endpoint Sales Activity Dashboard (Manager Only).
 * Gaya sama seperti CostumersValidationIndex: search/per_page/sort_by/sort_dir,
 * ditambah parameter rentang tanggal (date / start_date+end_date / preset).
 */
class SalesActivityValidationIndex extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // mode "day" — tanggal tunggal, dipakai kalau dikirim
            'date'       => 'nullable|date_format:Y-m-d',

            // mode "range" — dipakai kalau 'date' tidak dikirim
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',

            // shortcut preset biar frontend tidak perlu hitung tanggal sendiri
            // (persis sama dengan preset di sisi Vue: last7/lastWeek/last30/lastMonth)
            'preset'     => 'nullable|in:today,yesterday,last7,last_week,last30,last_month',

            'type'       => 'nullable|in:all,visit,followup,direct',
            'search'     => 'nullable|string|max:100',
            'per_page'   => 'nullable|integer|min:5|max:100',
            'sort_by'    => 'nullable|in:time,name',
            'sort_dir'   => 'nullable|in:asc,desc,Asc,Desc',
        ];
    }
}