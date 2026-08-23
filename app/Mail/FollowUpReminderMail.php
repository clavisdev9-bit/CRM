<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email reminder follow-up -- dikirim oleh command console
 * `follow-up:send-reminders` (lihat App\Console\Commands\SendFollowUpReminders)
 * ke sales yang pegang follow-up itu (CC ke Manager/Admin), begitu
 * follow_ups.follow_up_at tinggal <= 12 jam lagi dan statusnya masih PENDING.
 *
 * $followUp array (bukan Eloquent model, karena follow_ups di project ini
 * selalu diakses lewat DB::table() query builder, bukan Model) -- field yang
 * dipakai: follow_up_code, follow_up_type, subject, notes, follow_up_date,
 * follow_up_time, sales_name, target_name.
 */
class FollowUpReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $followUp)
    {
    }

    public function envelope(): Envelope
    {
        $target = $this->followUp['target_name'] ?? 'Customer';

        return new Envelope(
            subject: "Reminder Follow Up: {$target} -- jatuh tempo {$this->followUp['follow_up_date']} {$this->followUp['follow_up_time']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.follow-up-reminder',
            with: [
                'followUp' => $this->followUp,
            ],
        );
    }
}