<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Logs;
use Lemonfiber\Sdk\LogWindow;
use Modules\Kernel\Api\Scrollback;
use Modules\Sdk\Api\LineIsUnreadable;
use Modules\Sdk\Api\Lines;

use function sprintf;

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

it('a line with no `at` key at all is a line with no moment', function (): void {
    $window = Lines::in(aWindowOf('gluetun', 10, [
        ['line' => 'tunnel up', 'service' => 'gluetun', 'stream' => 'stdout'],
    ]));

    expect($window->count())->toBe(1);
});
