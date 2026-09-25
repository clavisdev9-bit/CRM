<?php

namespace App\Console\Commands\Concerns;

use App\Models\MsUsers;

/**
 * ── Dipakai bareng oleh command-command Agenda Notification (Fase 2):
 * ProcessFollowUpAgendaReminders, ProcessFollowUpOverdueNotifications,
 * ProcessVisitPlanAgendaReminders -- resolve penerima notifikasi Agenda
 * untuk 1 baris follow_up/sales_visit_plan: SELALU sales pemiliknya
 * sendiri, DITAMBAH manager/atasan langsungnya (MsUsers::manager(),
 * lewat kolom manager_id) kalau ada & masih aktif. Disatukan di sini
 * supaya ke-3 command itu tidak duplikasi logic yang sama persis. ──
 */
trait ResolvesAgendaRecipients
{
    protected function resolveAgendaRecipients(int $salesId): array
    {
        $sales = MsUsers::with('manager')->find($salesId);

        if (!$sales || !$sales->is_active) {
            return [];
        }

        $recipientIds = [$sales->id_user];

        if (
            $sales->manager
            && $sales->manager->is_active
            && $sales->manager->id_user !== $sales->id_user
        ) {
            $recipientIds[] = $sales->manager->id_user;
        }

        return array_values(array_unique($recipientIds));
    }
}