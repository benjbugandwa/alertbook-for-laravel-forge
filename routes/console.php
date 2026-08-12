<?php

use App\Mail\IncidentSlaOverdueMail;
use App\Models\User;
use App\Services\IncidentSlaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('incidents:notify-sla', function () {
    $slaService = app(IncidentSlaService::class);

    $users = User::query()
        ->where('is_active', true)
        ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['superadmin', 'admin', 'superviseur']))
        ->get();

    $sent = 0;

    foreach ($users as $user) {
        $province = $user->hasRole('superadmin') ? null : $user->code_province;
        $incidents = $slaService->overdueIncidents($province, null, 20);

        if ($incidents->isEmpty()) {
            continue;
        }

        Mail::to($user->email)->queue(new IncidentSlaOverdueMail(
            incidents: $incidents,
            summary: $slaService->summary($province),
            recipientName: $user->name ?? $user->email
        ));

        $sent++;
    }

    $this->info("Notifications SLA envoyées : {$sent}");
})->purpose('Notify admins and supervisors about overdue incident SLAs');

Schedule::command('incidents:notify-sla')
    ->dailyAt(config('alertbook.sla_notification_time', '07:00'))
    ->withoutOverlapping(120)
    ->onOneServer()
    ->when(fn (): bool => config('alertbook.sla_notifications_enabled', false));
