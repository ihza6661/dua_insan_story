<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule activation of invitations every 15 minutes
Schedule::command('invitations:activate-scheduled')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Scheduled invitations activated successfully');
    })
    ->onFailure(function () {
        Log::error('Failed to activate scheduled invitations');
    });

// Send reminder emails daily at 10:00 AM for invitations scheduled within 24 hours
Schedule::command('invitations:send-reminders')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Scheduled activation reminders sent successfully');
    })
    ->onFailure(function () {
        Log::error('Failed to send scheduled activation reminders');
    });
