<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Kernel\Api\WhatWasFoundOfTheVersions;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

it('holds the three versions, and an engine that did not answer as none', function (): void {
    $runs = WhatRunsHere::namingNoRelease('0.16.0', '0.9.0', '  ', HowTheNotesStand::Pending);

    expect([$runs->lemonfiber(), $runs->stack(), $runs->engine(), $runs->notes()])
        ->toBe(['0.16.0', '0.9.0', '', HowTheNotesStand::Pending]);
});

it('refuses a blank lemonfiber or stack version, naming which', function (string $lemonfiber, string $stack, string $named): void {
    expect(static fn(): WhatRunsHere => WhatRunsHere::namingNoRelease($lemonfiber, $stack, '', HowTheNotesStand::Current))
        ->toThrow(VersionIsBlank::class, sprintf('`%s`', $named));
})->with([
    'lemonfiber' => [' ', '0.9.0', 'lemonfiber'],
    'stack' => ['0.16.0', '', 'stack'],
]);

it('hands the running release and its notes to one arm, and a stack naming none to the other', function (): void {
    $named = WhatRunsHere::reported(
        '0.16.0',
        '0.9.0',
        'v2',
        HowTheNotesStand::Current,
        Release::called('0.16.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        AGroupOfChanges::titled('New', 'One', 'Two'),
    );
    $arms = static fn(WhatRunsHere $runs): string => $runs->running(
        named: static fn(Release $release, array $changes): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s %s %s', $release->version(), $changes[0]->title(), implode(',', iterator_to_array($changes[0], preserve_keys: false)))),
        notNamed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'),
    )->said;

    expect($arms($named))->toBe('0.16.0 New One,Two')
        ->and($arms(WhatRunsHere::namingNoRelease('0.16.0', '0.9.0', '', HowTheNotesStand::Current)))->toBe('-');
});

it('refuses a group of changes with no title', function (): void {
    expect(static fn(): AGroupOfChanges => AGroupOfChanges::titled(' ', 'One'))->toThrow(VersionIsBlank::class);
});

it('answers what was found, or the obstacle met instead', function (): void {
    $runs = WhatRunsHere::namingNoRelease('0.16.0', '0.9.0', '', HowTheNotesStand::Stale);
    $said = static fn(WhatWasFoundOfTheVersions $found): string => $found->either(
        found: static fn(WhatRunsHere $runs): TheWordCarriedOut => new TheWordCarriedOut($runs->lemonfiber()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;

    expect($said(WhatWasFoundOfTheVersions::found($runs)))->toBe('0.16.0')
        ->and($said(WhatWasFoundOfTheVersions::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(KindOfObstacle::StackDidNotAnswer->value);
});
