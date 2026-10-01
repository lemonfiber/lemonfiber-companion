<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Sdk\Api\LifecycleIsUnreadable;
use Modules\Sdk\Api\Lifecycles;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `lifecycle` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function lifecycleSaying(mixed $data): Envelope
{
    return new Envelope(1, 'lifecycle', $data);
}

/**
 * One service a restart waited for, as a stack sends it.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function aServiceItWaitedFor(array $differently = []): array
{
    return [...[
        'id' => 'sonarr',
        'name' => 'Sonarr',
        'describes' => 'Fetches series',
        'state' => 'failed',
        'criticality' => 'important',
        'depends_on' => ['prowlarr'],
        'forms' => ['hunt'],
        'profile' => 'arr',
        'exit' => 137,
    ], ...$differently];
}

/**
 * The service a restart's plan left out, as a stack sends it.
 *
 * @return array<string, mixed>
 */
function aServiceThePlanLeftOut(): array
{
    return ['id' => 'qbittorrent', 'name' => 'qBittorrent', 'profile' => 'torrent', 'needs' => 'torrent', 'forms' => ['hunt']];
}

/**
 * A restart's plan, changed where a case says.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function aPlanInFull(array $differently = []): array
{
    return [...[
        'forms' => ['hunt'],
        'profiles' => ['arr'],
        'services' => ['sonarr', 'radarr'],
        'dropped' => [['profile' => 'torrent', 'needs' => 'torrent']],
        'filtered' => [aServiceThePlanLeftOut()],
        'footprint' => ['estimated_mib' => 700, 'unestimated' => []],
    ], ...$differently];
}

/**
 * A port a restart found held, changed where a case says.
 *
 * @param  array<string, mixed> $differently
 * @return array<string, mixed>
 */
function aPortTheRestartFoundHeld(array $differently = []): array
{
    return [...['port' => 8989, 'wanted_by' => 'sonarr', 'held_by' => 'media'], ...$differently];
}

/**
 * A restart's report with everything said.
 *
 * @return array<string, mixed>
 */
function aRestartInFull(): array
{
    return [
        'action' => 'restart',
        'command' => ['docker', 'compose', 'restart'],
        'condition' => 'partial',
        'plan' => aPlanInFull(),
        'port_conflicts' => [aPortTheRestartFoundHeld()],
        'rehearsed' => false,
        'services' => [aServiceItWaitedFor(), aServiceItWaitedFor(['id' => 'radarr', 'name' => 'Radarr', 'state' => 'healthy', 'exit' => null])],
        'stack_edits' => [],
        'status' => 0,
    ];
}

/** What a report read, as one line. */
function theRestartRead(mixed $data): string
{
    $report = Lifecycles::in(lifecycleSaying($data));
    $notBack = [];
    $leftOut = [];
    $ports = [];

    foreach ($report->whatDidNotComeBack() as $service) {
        $notBack[] = sprintf('%s:%s', $service->name(), $service->runs()->value);
    }

    foreach ($report->leftOut() as $service) {
        $leftOut[] = sprintf('%s:%s', $service->name(), $service->needs()->value);
    }

    foreach ($report->portsHeld() as $held) {
        $ports[] = sprintf('%d:%s:%s', $held->port(), $held->wantedBy(), $held->heldBy());
    }

    return sprintf(
        '%s / %s / not back %s / left out %s / ports %s / %s',
        $report->was()->value,
        $report->amountsTo(
            said: static fn(HowTheStackIsRunning $condition): TheWordCarriedOut => new TheWordCarriedOut($condition->value),
            unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
        )->said,
        implode(',', $notBack),
        implode(',', $leftOut),
        implode(',', $ports),
        $report->whetherItRan(
            ran: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ran'),
            declined: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('declined: %s', $why)),
        )->said,
    );
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('LifecycleEnvelope', ['api_version' => 1, 'kind' => 'lifecycle', 'data' => aRestartInFull()]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('LifecycleEnvelope', ['api_version' => 1, 'kind' => 'lifecycle', 'data' => [
            ...aRestartInFull(),
            'held' => 'The stack was stopped on purpose.',
            'condition' => null,
        ]]))->toBe([]);
});

it('reads what it amounts to, what did not come back, what was left out and which ports are held', function (): void {
    expect(theRestartRead(aRestartInFull()))
        ->toBe('carried_out / partial / not back Sonarr:failed / left out qBittorrent:torrent / ports 8989:sonarr:media / ran');
});

it('reads a rehearsal as one', function (): void {
    expect(theRestartRead([...aRestartInFull(), 'rehearsed' => true]))->toStartWith('rehearsed / ');
});

it('reads a start the stack declined, with its reason', function (): void {
    expect(theRestartRead([...aRestartInFull(), 'held' => 'This machine is on its battery.']))->toEndWith('/ declined: This machine is on its battery.')
        ->and(theRestartRead([...aRestartInFull(), 'held' => null]))->toEndWith('/ ran');
});

it('reads a condition left out, or sent as nothing, as unsaid', function (): void {
    expect(theRestartRead(array_diff_key(aRestartInFull(), ['condition' => true])))->toContain(' / unsaid / ')
        ->and(theRestartRead([...aRestartInFull(), 'condition' => null]))->toContain(' / unsaid / ');
});

it('reads ports left out as none held, which is the contract\'s own default', function (): void {
    expect(theRestartRead(array_diff_key(aRestartInFull(), ['port_conflicts' => true])))->toContain('/ ports  /');
});

it('reads a verb that waited for nothing and left nothing out', function (): void {
    $report = Lifecycles::in(lifecycleSaying([...aRestartInFull(), 'services' => [], 'port_conflicts' => [], 'plan' => aPlanInFull(['filtered' => []])]));

    expect($report->whatDidNotComeBack())->toHaveCount(0)
        ->and($report->leftOut())->toHaveCount(0)
        ->and($report->portsHeld())->toHaveCount(0);
});

it('refuses a payload that is not a table, or a part that is absent or not what the contract says', function (mixed $data, string $field): void {
    expect(fn(): WhatTheVerbCameTo => Lifecycles::in(lifecycleSaying($data)))
        ->toThrow(LifecycleIsUnreadable::class, sprintf('no readable `%s`', $field));
})->with([
    ['nothing', 'data'],
    [array_diff_key(aRestartInFull(), ['rehearsed' => true]), 'rehearsed'],
    [[...aRestartInFull(), 'rehearsed' => 'no'], 'rehearsed'],
    [array_diff_key(aRestartInFull(), ['services' => true]), 'services'],
    [[...aRestartInFull(), 'services' => 'sonarr'], 'services'],
    [array_diff_key(aRestartInFull(), ['plan' => true]), 'plan'],
    [[...aRestartInFull(), 'plan' => 'hunt'], 'plan'],
    [[...aRestartInFull(), 'plan' => array_diff_key(aPlanInFull(), ['filtered' => true])], 'filtered'],
    [[...aRestartInFull(), 'plan' => aPlanInFull(['filtered' => 'qbittorrent'])], 'filtered'],
    [[...aRestartInFull(), 'port_conflicts' => 'media'], 'port_conflicts'],
    [[...aRestartInFull(), 'condition' => 'fine'], 'condition'],
    [[...aRestartInFull(), 'condition' => 7], 'condition'],
    [[...aRestartInFull(), 'condition' => ''], 'condition'],
    [[...aRestartInFull(), 'held' => '  '], 'held'],
    [[...aRestartInFull(), 'held' => 7], 'held'],
]);

it('refuses an entry that is not what the contract says, naming its list and position', function (mixed $data, string $field): void {
    expect(fn(): WhatTheVerbCameTo => Lifecycles::in(lifecycleSaying($data)))
        ->toThrow(LifecycleIsUnreadable::class, sprintf('Entry 1 of the lifecycle\'s `%s`', $field));
})->with([
    [[...aRestartInFull(), 'services' => [aServiceItWaitedFor(), 'radarr']], 'services'],
    [[...aRestartInFull(), 'services' => [aServiceItWaitedFor(), aServiceItWaitedFor(['name' => ' '])]], 'services'],
    [[...aRestartInFull(), 'services' => [aServiceItWaitedFor(), array_diff_key(aServiceItWaitedFor(), ['name' => true])]], 'services'],
    [[...aRestartInFull(), 'services' => [aServiceItWaitedFor(), aServiceItWaitedFor(['state' => 'resting'])]], 'services'],
    [[...aRestartInFull(), 'services' => [aServiceItWaitedFor(), array_diff_key(aServiceItWaitedFor(), ['state' => true])]], 'services'],
    [[...aRestartInFull(), 'plan' => aPlanInFull(['filtered' => [aServiceThePlanLeftOut(), ['id' => 'nzbget']]])], 'filtered'],
    [[...aRestartInFull(), 'port_conflicts' => [aPortTheRestartFoundHeld(), 8096]], 'port_conflicts'],
    [[...aRestartInFull(), 'port_conflicts' => [aPortTheRestartFoundHeld(), aPortTheRestartFoundHeld(['port' => '8096'])]], 'port_conflicts'],
    [[...aRestartInFull(), 'port_conflicts' => [aPortTheRestartFoundHeld(), array_diff_key(aPortTheRestartFoundHeld(), ['port' => true])]], 'port_conflicts'],
    [[...aRestartInFull(), 'port_conflicts' => [aPortTheRestartFoundHeld(), aPortTheRestartFoundHeld(['wanted_by' => ' '])]], 'port_conflicts'],
    [[...aRestartInFull(), 'port_conflicts' => [aPortTheRestartFoundHeld(), aPortTheRestartFoundHeld(['held_by' => ''])]], 'port_conflicts'],
]);

it('refuses the first entry by its position, not as a list that is missing', function (): void {
    expect(fn(): WhatTheVerbCameTo => Lifecycles::in(lifecycleSaying([...aRestartInFull(), 'services' => ['sonarr']])))
        ->toThrow(LifecycleIsUnreadable::class, 'Entry 0 of the lifecycle\'s `services`')
        ->and(fn(): WhatTheVerbCameTo => Lifecycles::in(lifecycleSaying([...aRestartInFull(), 'port_conflicts' => [8989]])))
        ->toThrow(LifecycleIsUnreadable::class, 'Entry 0 of the lifecycle\'s `port_conflicts`');
});
