<?php

declare(strict_types=1);

use Modules\Kernel\Api\AHealingAct;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\KindOfMonitor;
use Modules\Kernel\Api\WhatBecameOfAnAct;
use Modules\Kernel\Api\WhetherHealingActs;
use Modules\Kernel\Api\WhichWayItLooks;
use Modules\Kernel\Api\WhyItIsWedged;
use Tests\Support\TheGeneratedEnvelopes;
use Tests\Support\TheWireUnions;

// The watcher's, the queue's and a run-and-exit service's values, each held to the generated envelope it is read from, as `EveryWireValueIsACaseTest` holds the rest.

it('every kind, state and path of a monitor the contract describes has a case', function (): void {
    $watcher = TheGeneratedEnvelopes::theGeneratedEnvelope('WatcherEnvelope');

    expect(TheWireUnions::unionIn($watcher, 'kind'))->not->toBe([], 'no kind of monitor was found in the generated watcher envelope')
        ->and(TheWireUnions::valuesOf(KindOfMonitor::cases()))->toBe(TheWireUnions::unionIn($watcher, 'kind'))
        ->and(TheWireUnions::valuesOf(HowAMonitorStands::cases()))->toBe(TheWireUnions::unionIn($watcher, 'state'))
        ->and(TheWireUnions::valuesOf(WhichWayItLooks::cases()))->toBe(TheWireUnions::unionIn($watcher, 'path'));
});

it('every switch, class, act and outcome of the queue\'s healing the contract describes has a case', function (): void {
    $queue = TheGeneratedEnvelopes::theGeneratedEnvelope('QueueEnvelope');

    expect(TheWireUnions::unionIn($queue, 'class'))->not->toBe([], 'no class of unhealthy item was found in the generated queue envelope')
        ->and(TheWireUnions::valuesOf(WhetherHealingActs::cases()))->toBe(TheWireUnions::unionIn($queue, 'healing'))
        ->and(TheWireUnions::valuesOf(WhyItIsWedged::cases()))->toBe(TheWireUnions::unionIn($queue, 'class'))
        ->and(TheWireUnions::valuesOf(AHealingAct::cases()))->toBe(TheWireUnions::unionIn($queue, 'act'))
        ->and(TheWireUnions::valuesOf(WhatBecameOfAnAct::cases()))->toBe(TheWireUnions::unionIn($queue, 'outcome'));
});

it('every health of a run-and-exit service the contract describes has a case', function (): void {
    $status = TheGeneratedEnvelopes::theGeneratedEnvelope('StatusEnvelope');

    expect(TheWireUnions::unionIn($status, 'health'))->not->toBe([], 'no run health was found in the generated status envelope')
        ->and(TheWireUnions::valuesOf(HowItsLastRunWent::cases()))->toBe(TheWireUnions::unionIn($status, 'health'));
});
