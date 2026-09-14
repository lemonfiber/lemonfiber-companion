<?php

declare(strict_types=1);

use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stream;

/** One word carried out of `when()`, since it must hand back an object. */
final readonly class WhatTheMomentSaid
{
    public function __construct(public string $said) {}
}

/** A line's moment, or the word for not having one. */
function whenOneLineHappened(Said $said): string
{
    return $said->when(
        then: static fn(string $when): WhatTheMomentSaid => new WhatTheMomentSaid($when),
        unstated: static fn(): WhatTheMomentSaid => new WhatTheMomentSaid('unstated'),
    )->said;
}

it('N2-R10 — carries the line, the service and which mouth it came out of', function (): void {
    $line = Said::whenever('no route to host', ServiceId::called('gluetun'), Stream::Stderr);

    expect($line->line())->toBe('no route to host')
        ->and($line->service()->named())->toBe('gluetun')
        ->and($line->stream())->toBe(Stream::Stderr);
});

it('a moment the service recorded comes back as the service wrote it', function (): void {
    // Not reformatted and not put in the phone's timezone: two operators in
    // different places must not disagree about when something happened on the
    // same machine.
    $line = Said::at('2026-09-14T04:00:00Z', 'tunnel up', ServiceId::called('gluetun'), Stream::Stdout);

    expect(whenOneLineHappened($line))->toBe('2026-09-14T04:00:00Z');
});

it('a service that wrote no moment is ordinary, not a fault', function (): void {
    $line = Said::whenever('tunnel up', ServiceId::called('gluetun'), Stream::Stdout);

    expect(whenOneLineHappened($line))->toBe('unstated');
});

it('a blank line is a line', function (): void {
    // Services print them to separate one stanza from the next, and a reader
    // that dropped them would join two unrelated passages into one paragraph —
    // which reads as something the service said.
    $line = Said::whenever('', ServiceId::called('gluetun'), Stream::Stdout);

    expect($line->line())->toBe('');
});

it('N2-R10 — a line says whether it holds what somebody is looking for', function (): void {
    $line = Said::whenever('connection timed out', ServiceId::called('gluetun'), Stream::Stderr);

    expect($line->holds(LookingFor::text('timed')))->toBeTrue()
        ->and($line->holds(LookingFor::text('refused')))->toBeFalse();
});

it('the search does not care about case', function (): void {
    // Somebody scanning for a word should not have to know whether the service
    // shouted it.
    $line = Said::whenever('Connection TIMED OUT', ServiceId::called('gluetun'), Stream::Stderr);

    expect($line->holds(LookingFor::text('timed out')))->toBeTrue();
});

it('the search reads the text and not the service name', function (): void {
    // The service is the same on every line of a window, so matching it would
    // select all of them or none — neither of which is a search.
    $line = Said::whenever('tunnel up', ServiceId::called('gluetun'), Stream::Stdout);

    expect($line->holds(LookingFor::text('gluetun')))->toBeFalse();
});

it('a multi-byte line matches on a multi-byte term', function (): void {
    // `mb_stripos` rather than a byte-wise case fold, which would turn a
    // multi-byte character into something that matches nothing.
    $line = Said::whenever('verbinding verbroken — geen route', ServiceId::called('gluetun'), Stream::Stderr);

    expect($line->holds(LookingFor::text('—')))->toBeTrue()
        ->and($line->holds(LookingFor::text('GEEN')))->toBeTrue();
});
