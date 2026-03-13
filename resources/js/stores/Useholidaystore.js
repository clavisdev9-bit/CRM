/**
 * useHolidayStore.js
 * Store untuk data libur nasional Indonesia
 * Sumber: https://libur.deno.dev (gratis, tanpa API key, data resmi pemerintah)
 *
 * Endpoint:
 *   GET https://libur.deno.dev/api?year=2026&month=3
 *   Response: [{ date: "2026-03-28", name: "Hari Raya Nyepi" }, ...]
 *
 *   GET https://libur.deno.dev/api?year=2026&month=3&day=28
 *   Response: { is_holiday: true, name: "Hari Raya Nyepi" }
 */

import { ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

const BASE_URL = 'https://libur.deno.dev/api'

export const useHolidayStore = defineStore('Holiday', () => {

    // Cache: key = "YYYY-MM" → array of { date, name }
    const holidayCache = ref({})
    const loadingHoliday = ref(false)

    /**
     * Ambil daftar libur untuk bulan & tahun tertentu
     * Hasilnya di-cache supaya tidak hit API berulang kali
     */
    const fetchHolidays = async (month, year) => {
        const key = `${year}-${String(month).padStart(2, '0')}`
        if (holidayCache.value[key]) return // sudah ada di cache

        loadingHoliday.value = true
        try {
            const res = await axios.get(`${BASE_URL}?year=${year}&month=${month}`)
            // Response: array [{ date, name }] atau object { is_holiday, name }
            const data = Array.isArray(res.data) ? res.data : []
            holidayCache.value[key] = data
        } catch (err) {
            console.warn(`Gagal fetch libur nasional ${key}:`, err)
            holidayCache.value[key] = [] // fallback kosong supaya tidak retry terus
        } finally {
            loadingHoliday.value = false
        }
    }

    /**
     * Cek apakah tanggal tertentu adalah libur nasional
     * @param {string} dateStr - format "YYYY-MM-DD"
     * @returns {{ isHoliday: boolean, name: string|null }}
     */
    const checkHoliday = (dateStr) => {
        if (!dateStr) return { isHoliday: false, name: null }

        const [year, month] = dateStr.split('-')
        const key = `${year}-${month}`
        const holidays = holidayCache.value[key] ?? []

        const found = holidays.find(h => h.date === dateStr)
        return {
            isHoliday: !!found,
            name: found?.name ?? null,
        }
    }

    /**
     * Ambil semua libur untuk satu bulan (sudah di-cache)
     * @param {number} month
     * @param {number} year
     * @returns {Array} [{ date, name }]
     */
    const getHolidaysByMonth = (month, year) => {
        const key = `${year}-${String(month).padStart(2, '0')}`
        return holidayCache.value[key] ?? []
    }

    /**
     * Merge data attendance_days dengan data libur nasional
     * Menambahkan field `is_holiday` dan `holiday_name` ke setiap day
     * @param {Array} attendanceDays - dari store.attendanceDays
     * @param {number} month
     * @param {number} year
     * @returns {Array} attendanceDays yang sudah diperkaya
     */
    const mergeHolidaysWithDays = (attendanceDays, month, year) => {
        const holidays = getHolidaysByMonth(month, year)
        const holidayMap = {}
        holidays.forEach(h => { holidayMap[h.date] = h.name })

        return attendanceDays.map(day => ({
            ...day,
            is_holiday:   !!holidayMap[day.date],
            holiday_name: holidayMap[day.date] ?? null,
            // is_off = libur (weekend ATAU libur nasional)
            is_off: day.is_weekend || !!holidayMap[day.date],
        }))
    }

    return {
        holidayCache,
        loadingHoliday,
        fetchHolidays,
        checkHoliday,
        getHolidaysByMonth,
        mergeHolidaysWithDays,
    }
})