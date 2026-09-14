<?php

declare(strict_types=1);

use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Modules\Kernel\Api\WhatLeansOnIt;

/** One word carried out of `exit()`, since it must hand back an object. */
final readonly class WhatTheDaemonSaidAboutEnding
{
    public function __construct(public string $said) {}
}

/** How a service ended, or the word for nothing having said. */
function howItEnded(Daemon $daemon): string
{
    return $daemon->exit(
        said: static fn(int $code): WhatTheDaemonSaidAboutEnding
            => new WhatTheDaemonSaidAboutEnding((string) $code),
        unstated: static fn(): WhatTheDaemonSaidAboutEnding
            => new WhatTheDaemonSaidAboutEnding('unstated'),
    )->said;
}

/** A service, named so a test can read it back. */
function aDaemonCalled(string $name, HowAServiceRuns $runs = HowAServiceRuns::Healthy): Daemon
{
    return Daemon::called(
        $name,
        ServiceId::called($name),
        Form::called('media'),
        $runs,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    );
}

it('N2-R7 — carries what an operator needs before touching a service', function (): void {
    $daemon = aDaemonCalled('sonarr', HowAServiceRuns::Stopped);

    expect($daemon->name())->toBe('sonarr')
        ->and($daemon->id()->named())->toBe('sonarr')
        ->and($daemon->profile()->named())->toBe('media')
        ->and($daemon->runs())->toBe(HowAServiceRuns::Stopped)
        ->and($daemon->matters())->toBe(HowMuchItMatters::Important);
});

it('the name and the id are kept apart', function (): void {
    // A screen holding only the name cannot act; one holding only the id shows
    // somebody `sonarr-4` where *Sonarr* belongs.
    $daemon = Daemon::called(
        'Sonarr',
        ServiceId::called('sonarr-4'),
        Form::called('media'),
        HowAServiceRuns::Healthy,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    );

    expect($daemon->name())->toBe('Sonarr')
        ->and($daemon->id()->named())->toBe('sonarr-4');
});

it('refuses a service with no name to show', function (): void {
    expect(fn(): Daemon => Daemon::called(
        '   ',
        ServiceId::called('sonarr'),
        Form::called('media'),
        HowAServiceRuns::Healthy,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    ))->toThrow(ServiceIsUnnamed::class);
});

it('N2-R8 — carries what stopping it would take with it', function (): void {
    $daemon = Daemon::called(
        'gluetun',
        ServiceId::called('gluetun'),
        Form::called('network'),
        HowAServiceRuns::Healthy,
        HowMuchItMatters::Critical,
        WhatLeansOnIt::these(ServiceId::called('qbittorrent'), ServiceId::called('prowlarr')),
    );

    expect($daemon->whatLeansOnIt()->count())->toBe(2);
});

it('a service that ended with a code says so, and one that did not says that', function (): void {
    $ended = Daemon::thatExited(
        'sonarr',
        ServiceId::called('sonarr'),
        Form::called('media'),
        HowAServiceRuns::Failed,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
        137,
    );

    expect(howItEnded($ended))->toBe('137')
        ->and(howItEnded(aDaemonCalled('radarr')))->toBe('unstated');
});

it('a service that ended is refused for the reasons every service is', function (): void {
    // `thatExited()` goes through `called()`, so the blank-name refusal reaches
    // it too — which keeps a crashed service from being the one row that can
    // arrive unreadable.
    expect(fn(): Daemon => Daemon::thatExited(
        ' ',
        ServiceId::called('sonarr'),
        Form::called('media'),
        HowAServiceRuns::Failed,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
        1,
    ))->toThrow(ServiceIsUnnamed::class);
});
