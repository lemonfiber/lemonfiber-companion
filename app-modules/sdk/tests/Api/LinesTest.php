<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Logs;
use Lemonfiber\Sdk\LogWindow;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\Scrollback;
use Modules\Sdk\Api\LineIsUnreadable;
use Modules\Sdk\Api\Lines;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A window whose lines arrived on a wire version this app has never heard of.
 *
 * A function beside {@see aWindowOf()} rather than a line in the test, because
 * building a window is what throws the client's own checked exceptions and a
 * closure may not let one out.
 *
 * @param list<array<mixed>> $rows
 */
function aWindowOnVersion(int $version, string $service, int $bound, array $rows): LogWindow
{
    $envelopes = [];

    foreach ($rows as $row) {
        $envelopes[] = new Envelope($version, 'log', $row);
    }

    return LogWindow::of(Logs::ofService($service, $bound), $envelopes);
}

/**
 * A log window holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see stuckSaying()}'s reason: what is
 * under test is what happens when the wire says something the contract does not
 * allow, which a client honouring the contract could never produce.
 *
 * @param list<array<mixed>> $rows
 */
function aWindowOf(string $service, int $bound, array $rows): LogWindow
{
    $envelopes = [];

    foreach ($rows as $row) {
        $envelopes[] = new Envelope(1, 'log', $row);
    }

    return LogWindow::of(Logs::ofService($service, $bound), $envelopes);
}

/**
 * One line, with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aLogRow(string $line, string $service, string $stream, ?string $at = null): array
{
    return ['at' => $at, 'line' => $line, 'service' => $service, 'stream' => $stream];
}

/** Every line of a window, folded to one string, so the order can be read. */
function everyLineFolded(Scrollback $scrollback): string
{
    $rows = [];

    foreach ($scrollback as $line) {
        $rows[] = sprintf('%s/%s', $line->stream()->value, $line->line());
    }

    return implode(' | ', $rows);
}

it('N2-R10 — reads a window, keeping the order the service wrote in', function (): void {
    $window = Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('tunnel up', 'gluetun', 'stdout', '2026-09-14T04:00:00Z'),
        aLogRow('no route to host', 'gluetun', 'stderr'),
    ]));

    expect(everyLineFolded($window))->toBe('stdout/tunnel up | stderr/no route to host');
});

it('N2-R10 — carries the service and the bound off the window itself', function (): void {
    // Taken from the SDK's own window rather than rebuilt here, because that
    // window already pairs what was asked for with what arrived — and a second
    // copy of the one decision this requirement turns on is the copy that
    // drifts.
    $window = Lines::in(aWindowOf('sonarr', 50, [aLogRow('scanning', 'sonarr', 'stdout')]));

    expect($window->service()->named())->toBe('sonarr')
        ->and($window->asked()->figure())->toBe(50)
        ->and($window->isAWindow())->toBeFalse();
});

it('a blank line is kept, because a service printed it', function (): void {
    // Dropping it would join two unrelated stanzas into one paragraph, which
    // reads as something the service said.
    $window = Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('first', 'gluetun', 'stdout'),
        aLogRow('', 'gluetun', 'stdout'),
        aLogRow('second', 'gluetun', 'stdout'),
    ]));

    expect(everyLineFolded($window))->toBe('stdout/first | stdout/ | stdout/second');
});

it('refuses a line with no readable text at all', function (): void {
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        ['service' => 'gluetun', 'stream' => 'stdout'],
    ])))->toThrow(LineIsUnreadable::class, '`line`');
});

it('refuses a line whose text is not text', function (): void {
    // The contract says `line` is a string. A number there is an answer from a
    // version of lemonfiber this app cannot read, and rendering it would put a
    // field value on somebody's screen.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        ['line' => 41, 'service' => 'gluetun', 'stream' => 'stdout'],
    ])))->toThrow(LineIsUnreadable::class, '`line`');
});

it('refuses a line naming no service, where a blank line is fine', function (): void {
    // Same shape, opposite answers, which is why the reader spells the
    // difference at the call site rather than tightening one check for both.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('tunnel up', '   ', 'stdout'),
    ])))->toThrow(LineIsUnreadable::class, '`service`');
});

it('names the position of the line it refused rather than always the first', function (): void {
    // A good line followed by a bad one is the shortest window that tells a
    // counter going up from one that never moved.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('tunnel up', 'gluetun', 'stdout'),
        aLogRow('down', 'gluetun', 'shouting'),
    ])))->toThrow(LineIsUnreadable::class, 'Line 1');
});

it('a stream this app does not recognise names what it does read', function (): void {
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('down', 'gluetun', 'shouting'),
    ])))->toThrow(LineIsUnreadable::class, '`stdout`');
});

it('a moment stated as nothing is refused rather than treated as unstated', function (): void {
    // The two are different facts about the service: one wrote a timestamp it
    // could not fill in, and the other does not write them. Folding them would
    // hide a fault in whatever produced the line.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('tunnel up', 'gluetun', 'stdout', '   '),
    ])))->toThrow(LineIsUnreadable::class, 'not a moment');
});

it('the refusal quotes back the moment it could not read', function (): void {
    // The phrase *not a moment* is the same on every one of these, so a message
    // that had lost the value would satisfy a test reading only the phrase —
    // and somebody looking at a stack that writes `  ` where a timestamp goes
    // needs to see the `  `. It is the one part of the sentence that says which
    // line to go and look at.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        aLogRow('tunnel up', 'gluetun', 'stdout', '   '),
    ])))->toThrow(LineIsUnreadable::class, 'happened at `   `,');
});

it('a moment that is not text at all is refused, and quoted as nothing', function (): void {
    // A number where a timestamp goes is a stack with a different fault from
    // one writing blanks, and the reader must not hand it on: the message takes
    // a string, so a value that is not one is quoted as nothing rather than
    // coerced into a shape that would read as a moment somebody could look up.
    //
    // Written as a row rather than through `aLogRow()`, whose `at` is typed
    // `?string` — which is exactly why nothing had reached this arm.
    expect(fn(): Scrollback => Lines::in(aWindowOf('gluetun', 10, [
        ['at' => 7, 'line' => 'tunnel up', 'service' => 'gluetun', 'stream' => 'stdout'],
    ])))->toThrow(LineIsUnreadable::class, 'happened at ``,');
});

it('a line with no `at` key at all is a line with no moment', function (): void {
    $window = Lines::in(aWindowOf('gluetun', 10, [
        ['line' => 'tunnel up', 'service' => 'gluetun', 'stream' => 'stdout'],
    ]));

    expect($window->count())->toBe(1);
});

it('N1-R13 — a window on a wire version this app does not support is refused', function (): void {
    // The gate, at the only place it can stand for a log read. A window is many
    // envelopes rather than one, and the client asserts each line's *kind* as it
    // builds the window while saying nothing about its version — so a window on
    // a version nobody here has heard of was read and handed on as fact.
    $window = aWindowOnVersion(99, 'sonarr', 100, [
        aLogRow('something happened', 'sonarr', 'stdout'),
    ]);

    expect(fn(): Scrollback => Lines::in($window))->toThrow(EnvelopeIsNotRead::class);
});

it('stands in for a service with lines the contract would accept', function (): void {
    // Every line rather than the first: a window is a document per line, and
    // the one a fixture gets wrong is the one carrying the field the others
    // leave at its usual value — here, the line the service did not time.
    $rows = [
        aLogRow('tunnel up', 'gluetun', 'stdout', '2026-09-14T04:00:00Z'),
        aLogRow('no route to host', 'gluetun', 'stderr'),
    ];

    foreach ($rows as $at => $row) {
        expect(WhatTheContractAccepts::complaintsAbout('LogEnvelope', ['kind' => 'log', 'data' => $row]))
            ->toBe([], sprintf(
                "The payload this suite stands in for a service with is not one a stack would send: line %d.\n",
                $at,
            ));
    }
});
