<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IncidentNeedsValidationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public Incident $incident,
        public string $userName,
        public string $province = '-',
        public string $territoire = '-',
        public string $zoneSante = '-'
    ) {}

    public function build(): self
    {
        return $this->subject("Incident à valider — {$this->incident->code_incident}")
            ->view('emails.incident-needs-validation')
            ->with([
                'userName' => $this->userName,
                'codeIncident' => $this->incident->code_incident,
                'dateIncident' => optional($this->incident->date_incident)->format('Y-m-d'),
                'province' => $this->province,
                'territoire' => $this->territoire,
                'zoneSante' => $this->zoneSante,
                'severite' => $this->incident->severite ?? '-',
                'statut' => $this->incident->statut_incident ?? '-',
                'actionUrl' => route('incidents.index'),
                'localite' => $this->incident->localite ?? '-',
            ]);
    }
}
