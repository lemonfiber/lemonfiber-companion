<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Lemonfiber\Native\Wakes;
use Modules\Kernel\Api\StackId;
use Modules\Wayfinding\Api\HearingWhileAway;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(sprintf('%s {--stack=}', Wakes::COMMAND), function (HearingWhileAway $hearing): void {
    $woke = $this->option('stack');

    is_string($woke) && $woke !== '' ? $hearing->hearOnly(StackId::rememberedAs($woke)) : $hearing->hear();
})->purpose('Hear each stack the operator is signed in to, or the one a push woke the app for, and tell what it raised while the app was away');
