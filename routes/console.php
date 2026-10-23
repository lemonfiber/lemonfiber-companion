<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Lemonfiber\Native\Wakes;
use Modules\Wayfinding\Api\HearingWhileAway;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(Wakes::COMMAND, function (HearingWhileAway $hearing): void {
    $hearing->hear();
})->purpose('Hear each stack the operator is signed in to, and tell what it raised while the app was away');
