<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Sdk\Api\BandwidthIsUnreadable;
use Modules\Sdk\Api\HowTheLineIs;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `bandwidth` envelope holding whatever the case under test is about.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function bandwidthSaying(array $data): Envelope
{
    return new Envelope(1, 'bandwidth', $data);
}

/**
 * A line with every required part and nothing optional.
 *
 * @return array<string, mixed>
 */
function aPlainLine(): array
{
    return [
        'restraint' => 'limited',
        'means' => 'The stack takes a share and leaves the rest',
        'cautions' => ['Measured at night'],
        'untouched' => ['Plex streams'],
        'down' => ['limit' => ['as' => 'share', 'at' => 50], 'resolved' => ['is' => 'at', 'bytes_per_second' => 6_250_000], 'says' => 'Half of 100 Mbit/s'],
        'up' => ['limit' => ['as' => 'unlimited'], 'resolved' => ['is' => 'unlimited'], 'says' => 'No limit'],
        'respite' => ['standing' => 'none'],
        'clients' => [],
        'applied' => false,
    ];
}

/**
 * The optional parts, all present.
 *
 * @return array<string, mixed>
 */
function everythingItCouldKnow(): array
{
    return [
        'capacity' => ['down' => 12_500_000, 'up' => 2_500_000, 'source' => 'observed', 'taken' => 1_790_100_000, 'through_tunnel' => true],
        'cap' => ['monthly' => 1_000_000_000_000, 'exceeded' => 'throttle'],
        'reached' => 'warning',
        'acting' => 'Nothing is being held back yet',
        'ratio' => 'Seeding back at a quarter slows the ratio',
    ];
}

/** One line carried out of an arm. */
final readonly class WhatTheLineReadAs
{
    public function __construct(public string $said) {}
}

/** Everything a reading says, as one line. */
function everythingTheLineSays(HowTheLineIsShared $line): string
{
    return sprintf(
        '%s|%s|%s|%s|[%s]|[%s]|%s|%s|%s|%s',
        $line->stands()->value,
        $line->means(),
        $line->downSays(),
        $line->upSays(),
        implode(',', iterator_to_array($line->cautions(), preserve_keys: false)),
        implode(',', iterator_to_array($line->untouched(), preserve_keys: false)),
        $line->capacity(
            static fn(WhatTheLineCarries $c): WhatTheLineReadAs => new WhatTheLineReadAs(sprintf('%d/%d %s @%d %s', $c->down(), $c->up(), $c->measuredAs()->value, $c->taken()->epochSeconds(), $c->tunnel()->value)),
            static fn(): WhatTheLineReadAs => new WhatTheLineReadAs('unmeasured'),
        )->said,
        $line->cap(
            static fn(AMonthlyCap $c): WhatTheLineReadAs => new WhatTheLineReadAs(sprintf('%d %s %s', $c->monthly(), $c->does()->value, $c->stands(
                static fn(WhereTheMonthStands $m): WhatTheLineReadAs => new WhatTheLineReadAs($m->value),
                static fn(): WhatTheLineReadAs => new WhatTheLineReadAs('uncounted'),
            )->said)),
            static fn(): WhatTheLineReadAs => new WhatTheLineReadAs('uncapped'),
        )->said,
        $line->spentCap(static fn(string $doing): WhatTheLineReadAs => new WhatTheLineReadAs($doing), static fn(): WhatTheLineReadAs => new WhatTheLineReadAs('unspent'))->said,
        $line->uploadCost(static fn(string $costs): WhatTheLineReadAs => new WhatTheLineReadAs($costs), static fn(): WhatTheLineReadAs => new WhatTheLineReadAs('no upload cost'))->said,
    );
}

it('reads a line with nothing optional as knowing nothing optional', function (): void {
    expect(everythingTheLineSays(HowTheLineIs::in(bandwidthSaying(aPlainLine()))))
        ->toBe('limited|The stack takes a share and leaves the rest|Half of 100 Mbit/s|No limit|[Measured at night]|[Plex streams]|unmeasured|uncapped|unspent|no upload cost');
});

it('N10-R4, N10-R5, N10-R6 — reads the capacity, how it was measured, the tunnel, the cap and what it does', function (): void {
    expect(everythingTheLineSays(HowTheLineIs::in(bandwidthSaying([...aPlainLine(), ...everythingItCouldKnow()]))))
        ->toBe('limited|The stack takes a share and leaves the rest|Half of 100 Mbit/s|No limit|[Measured at night]|[Plex streams]|12500000/2500000 observed @1790100000 through|1000000000000 throttle warning|Nothing is being held back yet|Seeding back at a quarter slows the ratio');
});

it('N10-R7 — reads a cap of zero as a cap, and null as none declared', function (): void {
    $zero = HowTheLineIs::in(bandwidthSaying([...aPlainLine(), 'cap' => ['monthly' => 0, 'exceeded' => 'pause']]));
    $none = HowTheLineIs::in(bandwidthSaying([...aPlainLine(), 'cap' => null, 'capacity' => null, 'reached' => null, 'acting' => null, 'ratio' => null]));

    expect(everythingTheLineSays($zero))->toEndWith('|unmeasured|0 pause uncounted|unspent|no upload cost')
        ->and(everythingTheLineSays($none))->toEndWith('|unmeasured|uncapped|unspent|no upload cost');
});

it('reads every state the line can be in', function (WhereTheLineStands $stands): void {
    expect(HowTheLineIs::in(bandwidthSaying([...aPlainLine(), 'restraint' => $stands->value]))->stands())->toBe($stands);
})->with(WhereTheLineStands::cases());

it('refuses a standing with no cap to stand against', function (): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), 'reached' => 'warning'])))->toThrow(BandwidthIsUnreadable::class, '`cap`');
});

it('refuses a payload missing anything required, naming where', function (string $field, string $named): void {
    $line = aPlainLine();
    unset($line[$field]);

    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying($line)))->toThrow(BandwidthIsUnreadable::class, sprintf('`%s`', $named));
})->with([['restraint', 'restraint'], ['means', 'means'], ['cautions', 'cautions'], ['untouched', 'untouched'], ['down', 'down'], ['up', 'up']]);

it('refuses a direction whose sentence is missing, naming the direction', function (): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), 'up' => ['limit' => ['as' => 'unlimited'], 'resolved' => ['is' => 'unlimited'], 'says' => ' ']])))
        ->toThrow(BandwidthIsUnreadable::class, '`up.says`');
});

it('refuses an envelope whose data is not a payload', function (): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(new Envelope(1, 'bandwidth', 'nothing')))->toThrow(BandwidthIsUnreadable::class, '`data`');
});

it('refuses a caution or an untouched entry that is not a sentence, by position', function (string $list, mixed $said): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), $list => ['fine', $said]])))
        ->toThrow(BandwidthIsUnreadable::class, sprintf('Entry 1 of `%s`', $list));
})->with([['cautions', ' '], ['untouched', 3]]);

it('refuses words it has no case for, naming what it reads', function (array $overrides, string $where): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), ...everythingItCouldKnow(), ...$overrides])))
        ->toThrow(BandwidthIsUnreadable::class, sprintf('`%s` is', $where));
})->with([
    [['restraint' => 'throttled'], 'restraint'],
    [['capacity' => ['down' => 1, 'up' => 1, 'source' => 'guessed', 'taken' => 1, 'through_tunnel' => false]], 'capacity.source'],
    [['cap' => ['monthly' => 1, 'exceeded' => 'explode']], 'cap.exceeded'],
    [['reached' => 'nearly'], 'reached'],
]);

it('names every word it reads when it refuses one, each quoted, so the message is the whole set', function (): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), ...everythingItCouldKnow(), 'cap' => ['monthly' => 1, 'exceeded' => 'explode']])))
        ->toThrow(BandwidthIsUnreadable::class, 'this app reads `pause`, `throttle`, `continue`.');
});

it('refuses a capacity or cap whose figures cannot be counts', function (array $overrides, string $where): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), ...$overrides])))
        ->toThrow(BandwidthIsUnreadable::class, sprintf('`%s`', $where));
})->with([
    [['capacity' => ['down' => -1, 'up' => 1, 'source' => 'declared', 'taken' => 1, 'through_tunnel' => false]], 'capacity.down'],
    [['capacity' => ['down' => 1, 'up' => '2', 'source' => 'declared', 'taken' => 1, 'through_tunnel' => false]], 'capacity.up'],
    [['capacity' => ['down' => 1, 'up' => 1, 'source' => 'declared', 'taken' => 1, 'through_tunnel' => 'yes']], 'capacity.through_tunnel'],
    [['capacity' => 'fast'], 'capacity'],
    [['cap' => ['monthly' => -5, 'exceeded' => 'pause']], 'cap.monthly'],
]);

it('refuses a spent cap or upload cost said as a blank', function (string $field): void {
    expect(fn(): HowTheLineIsShared => HowTheLineIs::in(bandwidthSaying([...aPlainLine(), $field => ' '])))->toThrow(BandwidthIsUnreadable::class, sprintf('`%s`', $field));
})->with(['acting', 'ratio']);

it('judges the payloads these cases are built on against the contract', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BandwidthEnvelope', ['api_version' => 1, 'kind' => 'bandwidth', 'data' => aPlainLine()]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('BandwidthEnvelope', ['api_version' => 1, 'kind' => 'bandwidth', 'data' => [...aPlainLine(), ...everythingItCouldKnow()]]))->toBe([]);
});
