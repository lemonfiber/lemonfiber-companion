<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\TheTraceSaysNothing;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Sdk\Api\TraceIsUnreadable;
use Modules\Sdk\Api\Traces;

use function sprintf;

use Tests\Support\TracesToFollow;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `trace` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function traceSaying(mixed $data): Envelope
{
    return new Envelope(1, 'trace', $data);
}

/**
 * The series a stack sends, as data.
 *
 * @return array<string, mixed>
 */
function aTracedSeries(): array
{
    return TracesToFollow::theSeriesAsAStackSendsIt();
}

/** One line carried out of an arm. */
final readonly class WhatTheTraceCarried
{
    public function __construct(public string $said) {}
}

/**
 * Whether it was followed, how sure, how far, and how much is here, as one line.
 *
 * @param array<string, mixed> $data
 */
function theTraceRead(array $data): string
{
    return Traces::in(traceSaying($data))->either(
        nothingAskedFor: static fn(): WhatTheTraceCarried => new WhatTheTraceCarried('nothing asked for'),
        followed: static fn(WhatTheTraceFound $found): WhatTheTraceCarried => new WhatTheTraceCarried(sprintf(
            '%s|%s|%s|%d stages|%d moments|%d disagreements|%s',
            $found->sure()->value,
            $found->got()->furthest()->value,
            $found->got()->stall(),
            $found->got()->stages()->count(),
            $found->history()->count(),
            $found->disagreements()->count(),
            $found->here()->either(
                whole: static fn(): WhatTheTraceCarried => new WhatTheTraceCarried('whole'),
                inParts: static fn(ASeriesCounted $series): WhatTheTraceCarried => new WhatTheTraceCarried(sprintf('%d/%d in %d seasons', $series->have(), $series->wanted(), $series->seasons()->count())),
            )->said,
        )),
    )->said;
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('TraceEnvelope', TracesToFollow::whatAStackSaysOfTheSeries()))->toBe([]);
});

it('reads a followed series with everything it carries', function (): void {
    expect(theTraceRead(aTracedSeries()))->toBe('uncertain|downloading|No peers have been seen for two days|3 stages|3 moments|1 disagreements|8/10 in 2 seasons')
        ->and(Traces::in(traceSaying(aTracedSeries()))->item())->toBe('Severance');
});

it('reads a film as a whole item, and a trace with no stall as nothing stopping it', function (): void {
    expect(theTraceRead([...aTracedSeries(), 'coverage' => null, 'stall' => null]))->toBe('uncertain|downloading||3 stages|3 moments|1 disagreements|whole')
        ->and(theTraceRead(array_diff_key(aTracedSeries(), ['coverage' => true, 'stall' => true])))->toBe('uncertain|downloading||3 stages|3 moments|1 disagreements|whole');
});

it('reads a trace nothing matched as nobody asking for it, whatever else it carries', function (): void {
    expect(theTraceRead([...aTracedSeries(), 'matched' => false]))->toBe('nothing asked for')
        ->and(theTraceRead(['item' => 'Severance', 'matched' => false]))->toBe('nothing asked for');
});

it('refuses a payload that is not a table', function (): void {
    expect(fn(): WhereItGotTo => Traces::in(traceSaying('nothing')))->toThrow(TraceIsUnreadable::class, '`data`');
});

it('refuses a field that is missing or not what the contract says, naming it', function (string $field, mixed $said): void {
    $data = $said === 'absent' ? array_diff_key(aTracedSeries(), [$field => true]) : [...aTracedSeries(), $field => $said];

    expect(fn(): string => theTraceRead($data))->toThrow(TraceIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['item', 'absent'], ['item', ' '], ['matched', 'yes'], ['confidence', 7],
    ['furthest', 'absent'], ['stages', 'absent'], ['stages', ['a' => []]], ['history', 'none'],
    ['findings', [7]], ['stall', ' '], ['coverage', 'all of it'], ['confidence', ' '],
]);

it('refuses a word it has no case for, naming the field and every word it reads', function (string $field, string $said, string $accepts): void {
    expect(fn(): string => theTraceRead([...aTracedSeries(), $field => $said]))
        ->toThrow(TraceIsUnreadable::class, sprintf('`%s` is `%s`, and this app reads %s', $field, $said, $accepts));
})->with([
    ['confidence', 'likely', '`certain`, `uncertain`.'],
    ['furthest', 'lost', '`not-monitored`, `monitored`, `searching`, `found`, `grabbed`, `downloading`, `downloaded`, `importing`, `imported`, `available`.'],
]);

it('refuses a stage, a moment, a season or an episode that is not one', function (string $field, mixed $said, string $named): void {
    expect(fn(): string => theTraceRead([...aTracedSeries(), $field => $said]))->toThrow(TraceIsUnreadable::class, sprintf('`%s`', $named));
})->with([
    'a stage that is not a table' => ['stages', ['monitored'], 'stages'],
    'a stage with no service' => ['stages', [['stage' => 'grabbed', 'at' => null]], 'service'],
    'a stage with a blank time' => ['stages', [['stage' => 'grabbed', 'service' => 'sonarr', 'at' => ' ']], 'at'],
    'a moment that is no outcome' => ['history', [['outcome' => 'lost', 'at' => '2026-09-19']], 'outcome'],
    'a moment with no time' => ['history', [['outcome' => 'grabbed']], 'at'],
    'a season that is not a table' => ['coverage', ['have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'seasons' => [1]], 'seasons'],
    'a season with no count' => ['coverage', ['have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'seasons' => [['season' => 1, 'wanted' => 1, 'unmonitored' => 0, 'outstanding' => []]]], 'have'],
    'an episode that is not a table' => ['coverage', ['have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'seasons' => [['season' => 1, 'have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'outstanding' => ['E1']]]], 'outstanding'],
    'an episode with no title' => ['coverage', ['have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'seasons' => [['season' => 1, 'have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'outstanding' => [['season' => 1, 'number' => 1, 'title' => '', 'stage' => 'searching']]]]], 'title'],
    'coverage with no wanted' => ['coverage', ['have' => 0, 'unmonitored' => 0, 'seasons' => []], 'wanted'],
]);

it('refuses what cannot be, keeping the kernel refusal underneath', function (): void {
    try {
        theTraceRead([...aTracedSeries(), 'coverage' => ['have' => 11, 'wanted' => 10, 'unmonitored' => 0, 'seasons' => []]]);
        $underneath = null;
    } catch (TraceIsUnreadable $why) {
        $underneath = $why->getPrevious();
    }

    expect($underneath)->toBeInstanceOf(TheTraceSaysNothing::class);
});
