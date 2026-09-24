<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use LogicException;
use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Sdk\Api\OutboundIsUnreadable;
use Modules\Sdk\Api\WhatLeaves;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * An `outbound` envelope holding whatever the case under test is about.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function outboundSaying(array $data): Envelope
{
    return new Envelope(1, 'outbound', $data);
}

/**
 * One of lemonfiber's requests with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function oneOfOurRequests(): array
{
    return [
        'reach' => 'updates',
        'destination' => ['api.github.com'],
        'purpose' => 'To say when a newer lemonfiber is out',
        'sends' => 'Nothing but the request itself',
        'allowed' => true,
        'switch' => 'updates.check',
        'cost' => 'Nobody hears that a version came out',
    ];
}

/**
 * One service's row, recorded.
 *
 * @return array<string, mixed>
 */
function oneOfTheirRequests(string $service = 'sonarr'): array
{
    return ['service' => $service, 'destination' => 'thetvdb.com', 'purpose' => 'Series metadata', 'recorded' => true, 'origin' => ['origin' => 'bundled']];
}

/**
 * Both lists.
 *
 * @param list<mixed> $ours
 * @param list<mixed> $theirs
 *
 * @return Envelope<mixed>
 */
function whatLeavesAs(array $ours, array $theirs): Envelope
{
    return outboundSaying(['ours' => $ours, 'theirs' => $theirs]);
}

/** The first of lemonfiber's requests an answer holds, failing where it holds none. */
function theFirstOfOurs(WhatLeavesThisMachine $leaving): ARequestOfOurs
{
    foreach ($leaving->ours() as $request) {
        return $request;
    }

    throw new LogicException('The answer held none of lemonfiber\'s requests.');
}

/** The first service row an answer holds, failing where it holds none. */
function theFirstOfTheirs(WhatLeavesThisMachine $leaving): ARequestOfTheirs
{
    foreach ($leaving->theirs() as $request) {
        return $request;
    }

    throw new LogicException('The answer held no service row.');
}

/** One line carried out of an arm. */
final readonly class WhatTheirRequestSaid
{
    public function __construct(public string $said) {}
}

/** One of lemonfiber's requests, as one line. */
function oneLineFor(ARequestOfOurs $request): string
{
    return sprintf(
        '%s/%s/%s/%s/%s/%s/%s',
        $request->asksFor()->value,
        implode(',', iterator_to_array($request->destinations(), preserve_keys: false)),
        $request->purpose(),
        $request->sends(),
        $request->allowed()->value,
        $request->switch(),
        $request->cost(),
    );
}

/** One service's row, as one line. */
function oneLineForTheirs(ARequestOfTheirs $request): string
{
    return sprintf('%s/%s', $request->service()->named(), $request->reaches(
        recorded: static fn(string $destination, string $purpose): WhatTheirRequestSaid => new WhatTheirRequestSaid(sprintf('%s/%s', $destination, $purpose)),
        unrecorded: static fn(): WhatTheirRequestSaid => new WhatTheirRequestSaid('unrecorded'),
    )->said);
}

it('N10-R1, N10-R2, N10-R3 — reads lemonfiber\'s requests and its services\' as two lists', function (): void {
    $leaving = WhatLeaves::in(whatLeavesAs([oneOfOurRequests()], [oneOfTheirRequests()]));

    expect(array_map(oneLineFor(...), iterator_to_array($leaving->ours(), preserve_keys: false)))->toBe([
        'updates/api.github.com/To say when a newer lemonfiber is out/Nothing but the request itself/allowed/updates.check/Nobody hears that a version came out',
    ])->and(array_map(oneLineForTheirs(...), iterator_to_array($leaving->theirs(), preserve_keys: false)))->toBe(['sonarr/thetvdb.com/Series metadata']);
});

it('reads a request switched off, and one configured to reach nothing, as the two facts they are', function (): void {
    $leaving = WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'allowed' => false, 'destination' => []]], []));

    expect(theFirstOfOurs($leaving)->allowed())->toBe(WhetherItIsAllowed::SwitchedOff)
        ->and(theFirstOfOurs($leaving)->destinations())->toHaveCount(0);
});

it('reads a service recorded to reach nothing as reaching nothing', function (): void {
    $leaving = WhatLeaves::in(whatLeavesAs([], [[...oneOfTheirRequests('gluetun'), 'destination' => '']]));

    expect(oneLineForTheirs(theFirstOfTheirs($leaving)))->toBe('gluetun//Series metadata');
});

it('reads an unrecorded service as unknown, whatever words the stack filled in', function (): void {
    // The stack fills an unrecorded row's destination and purpose with words
    // of its own; read as a destination, they would be an answer nobody has.
    $leaving = WhatLeaves::in(whatLeavesAs([], [['service' => 'my-fork', 'destination' => 'unknown', 'purpose' => 'no record', 'recorded' => false, 'origin' => ['origin' => 'bundled']]]));

    expect(oneLineForTheirs(theFirstOfTheirs($leaving)))->toBe('my-fork/unrecorded');
});

it('reads every request lemonfiber can make', function (WhatLemonfiberAsksFor $asks): void {
    $leaving = WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'reach' => $asks->value]], []));

    expect(theFirstOfOurs($leaving)->asksFor())->toBe($asks);
})->with(WhatLemonfiberAsksFor::cases());

it('N10-R12 — a machine sending nothing is an answer', function (): void {
    $leaving = WhatLeaves::in(whatLeavesAs([], []));

    expect($leaving->ours())->toHaveCount(0)->and($leaving->theirs())->toHaveCount(0);
});

it('refuses a payload missing either list, or the data itself', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(outboundSaying(['theirs' => []])))->toThrow(OutboundIsUnreadable::class, '`ours`')
        ->and(fn(): WhatLeavesThisMachine => WhatLeaves::in(outboundSaying(['ours' => [], 'theirs' => 'some'])))->toThrow(OutboundIsUnreadable::class, '`theirs`')
        ->and(fn(): WhatLeavesThisMachine => WhatLeaves::in(new Envelope(1, 'outbound', 'nothing')))->toThrow(OutboundIsUnreadable::class, '`data`');
});

it('refuses a row that is not a connection, in either list, rather than dropping it', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([oneOfOurRequests(), 'x'], [])))->toThrow(OutboundIsUnreadable::class, 'Entry 1 of `ours`')
        ->and(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], ['x'])))->toThrow(OutboundIsUnreadable::class, 'Entry 0 of `theirs`')
        ->and(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [oneOfTheirRequests(), oneOfTheirRequests('radarr'), 'x'])))->toThrow(OutboundIsUnreadable::class, 'Entry 2 of `theirs`');
});

it('refuses one of lemonfiber\'s requests missing any word, naming which', function (string $field): void {
    $row = oneOfOurRequests();
    unset($row[$field]);

    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([$row], [])))
        ->toThrow(OutboundIsUnreadable::class, sprintf('Entry 0 of `ours` in the outbound envelope has no readable `%s`', $field));
})->with(['reach', 'destination', 'purpose', 'sends', 'allowed', 'switch', 'cost']);

it('refuses a word that is there but blank or not text, in either list', function (mixed $said): void {
    // Present and empty is not absent, and neither is a number: both are an
    // entry that will not say what it is.
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'sends' => $said]], [])))
        ->toThrow(OutboundIsUnreadable::class, 'Entry 0 of `ours` in the outbound envelope has no readable `sends`')
        ->and(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [[...oneOfTheirRequests(), 'service' => $said]])))
        ->toThrow(OutboundIsUnreadable::class, 'Entry 0 of `theirs` in the outbound envelope has no readable `service`');
})->with([['  '], [null], [3]]);

it('refuses a request lemonfiber has no word for, naming what it does read', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'reach' => 'telemetry']], [])))
        ->toThrow(OutboundIsUnreadable::class, '`telemetry`, and this app reads `registry`');
});

it('refuses a destination list holding something that is not a destination', function (mixed $one): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'destination' => ['ghcr.io', $one]]], [])))
        ->toThrow(OutboundIsUnreadable::class, '`destination`');
})->with([[' '], [3]]);

it('refuses an allowed that is not yes or no', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([[...oneOfOurRequests(), 'allowed' => 'yes']], [])))
        ->toThrow(OutboundIsUnreadable::class, '`allowed`');
});

it('refuses a service row missing what the reader needs, naming it', function (string $field): void {
    $row = oneOfTheirRequests();
    unset($row[$field]);

    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [$row])))
        ->toThrow(OutboundIsUnreadable::class, sprintf('Entry 0 of `theirs` in the outbound envelope has no readable `%s`', $field));
})->with(['service', 'recorded', 'destination', 'purpose']);

it('refuses a recorded service whose destination is not text', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [[...oneOfTheirRequests(), 'destination' => ['thetvdb.com']]])))
        ->toThrow(OutboundIsUnreadable::class, '`destination`');
});

it('judges the payload these cases are built on against the contract', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('OutboundEnvelope', ['api_version' => 1, 'kind' => 'outbound', 'data' => ['ours' => [oneOfOurRequests()], 'theirs' => [oneOfTheirRequests()]]]))
        ->toBe([]);
});

/** Who put a service there, carried out of the fold as one line. */
final readonly class WhoBroughtTheService
{
    public function __construct(public string $said) {}
}

/** Who a service's row says put it on the stack, as one line a case can compare. */
function whoPutTheServiceThere(ARequestOfTheirs $request): string
{
    return $request->origin()->whichever(
        bundled: static fn(): WhoBroughtTheService => new WhoBroughtTheService('bundled'),
        operator: static fn(): WhoBroughtTheService => new WhoBroughtTheService('operator'),
        plugin: static fn(string $named): WhoBroughtTheService => new WhoBroughtTheService(sprintf('plugin:%s', $named)),
        unknown: static fn(string $why): WhoBroughtTheService => new WhoBroughtTheService(sprintf('unknown:%s', $why)),
    )->said;
}

it('F7-R9 — reads who put each service there, on a recorded row and an unrecorded one alike', function (): void {
    // The unrecorded row is the one that matters: a plugin's service arrives
    // with no record of where it goes, and its origin is still read.
    $leaving = WhatLeaves::in(whatLeavesAs([], [
        oneOfTheirRequests(),
        [...oneOfTheirRequests('plex'), 'origin' => ['named' => 'plex', 'origin' => 'plugin']],
        ['service' => 'overseerr', 'destination' => 'unknown', 'purpose' => 'no record', 'recorded' => false, 'origin' => ['named' => 'plex', 'origin' => 'plugin']],
    ]));

    expect(array_map(whoPutTheServiceThere(...), iterator_to_array($leaving->theirs(), preserve_keys: false)))
        ->toBe(['bundled', 'plugin:plex', 'plugin:plex']);
});

it('refuses a service that cannot say who put it there, naming which entry', function (): void {
    // Second entry, so a refusal that dropped the position fails.
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [
        oneOfTheirRequests(),
        [...oneOfTheirRequests('plex'), 'origin' => ['origin' => 'plugin']],
    ])))->toThrow(OutboundIsUnreadable::class, 'Entry 1 of `theirs` in the outbound envelope cannot say where its service came from. It has no `named`');
});

it('refuses an unrecorded service with no origin, rather than skipping it on that arm', function (): void {
    expect(fn(): WhatLeavesThisMachine => WhatLeaves::in(whatLeavesAs([], [
        ['service' => 'overseerr', 'destination' => 'unknown', 'purpose' => 'no record', 'recorded' => false],
    ])))->toThrow(OutboundIsUnreadable::class, 'Entry 0 of `theirs`');
});
