<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class IncidentSlaOverdueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public Collection $incidents,
        public array $summary,
        public string $recipientName
    ) {}

    public function build(): self
    {
        return $this->subject('AlertBook - incidents en retard SLA')
            ->view('emails.incidents.sla-overdue')
            ->with([
                'incidents' => $this->incidents,
                'summary' => $this->summary,
                'recipientName' => $this->recipientName,
            ]);
    }
}
