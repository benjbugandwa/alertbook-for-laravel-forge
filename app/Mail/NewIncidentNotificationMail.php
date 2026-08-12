<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewIncidentNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public Incident $incident,
        public string $reportingOrg,
        public string $eventType,
        public string $provinceName
    ) {}

    public function build(): self
    {
        return $this->subject("Alerte : Nouveau rapport d'incident — {$this->incident->code_incident}")
            ->view('emails.incidents.new_notification')
            ->with([
                'codeIncident' => $this->incident->code_incident,
                'dateIncident' => $this->incident->created_at->format('d/m/Y'),
                'eventType' => $this->eventType,
                'reportingOrg' => $this->reportingOrg,
                'severite' => $this->incident->severite,
                'province' => $this->provinceName,
                'territoire' => $this->incident->territoire?->nom_territoire ?? '-',
            ]);
    }
}
