<?php

/**
 * ── Isi untuk routes/channels.php. Kalau file itu belum ada, buat baru
 * dengan isi persis ini. Kalau sudah ada (dari scaffolding Laravel
 * bawaan `App.Models.User` channel), TAMBAHKAN channel di bawah ini ke
 * file yang sudah ada -- jangan timpa channel lain yang mungkin sudah
 * ada di situ.
 *
 * Otorisasi: closure ini jalan tiap kali user coba subscribe ke channel
 * `notifications.{id_user}` lewat Laravel Echo. Harus return true kalau
 * user yang login BENAR pemilik channel itu (id_user di URL channel
 * sama dengan id_user yang login), supaya user A tidak pernah bisa
 * "nguping" notifikasi user B.
 * ──
 */

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications.{id_user}', function ($user, $id_user) {
    return (int) $user->id_user === (int) $id_user;
});