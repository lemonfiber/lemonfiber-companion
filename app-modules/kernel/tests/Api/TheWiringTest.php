<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\TheWiringSaysNothing;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WhatToDoAboutWiring;
use Modules\Kernel\Api\WhereAConnectionStands;

use function sprintf;

/** A connection that turned out as given, breaking nothing. */
function aConnectionThat(HowAConnectionEnded $ended, string $connection = 'SABnzbd into Sonarr'): AConnection
{
    return AConnection::of($connection, WhatItWouldBreak::nothing(), $ended);
}

it('keeps every connection in the stack\'s order, with how drift was judged, whether it only said so, and what it could not wire', function (): void {
    $unsupported = WhatIsUnsupported::these(Unsupported::of('tautulli', 'lemonfiber cannot speak to it'));
    $wiring = TheWiring::rehearsed(
        HowDriftWasJudged::Unassessable,
        $unsupported,
        ...['a' => aConnectionThat(HowAConnectionEnded::plainly(WhereAConnectionStands::Wired)), 'b' => aConnectionThat(HowAConnectionEnded::failed('401 Unauthorized'), 'qBittorrent into Radarr')],
    );
    $connections = [];

    foreach ($wiring as $connection) {
        $connections[] = $connection->connection();
    }

    expect($wiring->judged())->toBe(HowDriftWasJudged::Unassessable)
        ->and($wiring->wasRehearsed())->toBeTrue()
        ->and(TheWiring::written(HowDriftWasJudged::Assessed, WhatIsUnsupported::none())->wasRehearsed())->toBeFalse()
        ->and($wiring->unsupported())->toBe($unsupported)
        ->and($connections)->toBe(['SABnzbd into Sonarr', 'qBittorrent into Radarr'])
        ->and(array_keys(iterator_to_array($wiring, preserve_keys: true)))->toBe([0, 1])
        ->and($wiring)->toHaveCount(2);

    $written = TheWiring::written(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::none(),
        ...['a' => aConnectionThat(HowAConnectionEnded::plainly(WhereAConnectionStands::Wired)), 'b' => aConnectionThat(HowAConnectionEnded::failed('401 Unauthorized'), 'qBittorrent into Radarr')],
    );

    expect(array_keys(iterator_to_array($written, preserve_keys: true)))->toBe([0, 1]);
});

it('keeps what a connection connects, how serious it is, and how it ended, and refuses one that will not say what it connects', function (): void {
    $breaks = WhatItWouldBreak::warning('Downloads never reach the library', 'Point Sonarr at the download folder again');
    $ended = HowAConnectionEnded::plainly(WhereAConnectionStands::Drifted);
    $connection = AConnection::of('SABnzbd into Sonarr', $breaks, $ended);

    expect([$connection->connection(), $connection->breaks(), $connection->ended()])->toBe(['SABnzbd into Sonarr', $breaks, $ended])
        ->and(static fn(): AConnection => AConnection::of(' ', $breaks, $ended))->toThrow(TheWiringSaysNothing::class, '`connection`');
});

it('says what a warning breaks and what puts it right, and refuses a warning missing either', function (): void {
    $warning = WhatItWouldBreak::warning('Downloads never reach the library', 'Point Sonarr at the download folder again');
    $nothing = WhatItWouldBreak::nothing();

    expect([$warning->isAWarning(), $warning->breakage(), $warning->remediation()])->toBe([true, 'Downloads never reach the library', 'Point Sonarr at the download folder again'])
        ->and([$nothing->isAWarning(), $nothing->breakage(), $nothing->remediation()])->toBe([false, '', ''])
        ->and(static fn(): WhatItWouldBreak => WhatItWouldBreak::warning(' ', 'Point it again'))->toThrow(TheWiringSaysNothing::class, '`breakage`')
        ->and(static fn(): WhatItWouldBreak => WhatItWouldBreak::warning('It breaks', ''))->toThrow(TheWiringSaysNothing::class, '`remediation`');
});

it('carries nothing beside a state that says nothing more', function (WhereAConnectionStands $state): void {
    $ended = HowAConnectionEnded::plainly($state);

    expect([$ended->state(), $ended->said(), $ended->ours(), $ended->yours()])->toBe([$state, '', '', '']);
})->with([
    WhereAConnectionStands::Wired, WhereAConnectionStands::AlreadyWired, WhereAConnectionStands::Drifted, WhereAConnectionStands::Stale,
    WhereAConnectionStands::Adopted, WhereAConnectionStands::Unmanaged, WhereAConnectionStands::WouldAdopt,
]);

it('carries the reason for a connection observed, unmatched, skipped or refused, and refuses a blank one', function (WhereAConnectionStands $state): void {
    $ended = HowAConnectionEnded::because($state, 'The operator declared it unmanaged');

    expect([$ended->state(), $ended->said(), $ended->ours(), $ended->yours()])->toBe([$state, 'The operator declared it unmanaged', '', ''])
        ->and(static fn(): HowAConnectionEnded => HowAConnectionEnded::because($state, ' '))->toThrow(TheWiringSaysNothing::class, '`reason`');
})->with([WhereAConnectionStands::Observed, WhereAConnectionStands::Unmatched, WhereAConnectionStands::Skipped, WhereAConnectionStands::Refused]);

it('refuses a state built through a constructor that does not carry what it says', function (WhereAConnectionStands $state, string $through): void {
    $building = $through === 'plainly'
        ? static fn(): HowAConnectionEnded => HowAConnectionEnded::plainly($state)
        : static fn(): HowAConnectionEnded => HowAConnectionEnded::because($state, 'a reason');

    expect($building)->toThrow(TheWiringSaysNothing::class, sprintf('`%s`', $state->value));
})->with([
    [WhereAConnectionStands::Failed, 'plainly'], [WhereAConnectionStands::Skipped, 'plainly'], [WhereAConnectionStands::Unmatched, 'plainly'], [WhereAConnectionStands::Conflicted, 'plainly'],
    [WhereAConnectionStands::WouldWire, 'plainly'], [WhereAConnectionStands::Wired, 'because'], [WhereAConnectionStands::Failed, 'because'],
]);

it('carries a service\'s rejection in its own words, and refuses a failure without them', function (): void {
    $failed = HowAConnectionEnded::failed('401 Unauthorized: the API key is wrong');

    expect([$failed->state(), $failed->said(), $failed->ours(), $failed->yours()])->toBe([WhereAConnectionStands::Failed, '401 Unauthorized: the API key is wrong', '', ''])
        ->and(static fn(): HowAConnectionEnded => HowAConnectionEnded::failed(''))->toThrow(TheWiringSaysNothing::class, '`detail`')
        ->and(static fn(): HowAConnectionEnded => HowAConnectionEnded::failed('   '))->toThrow(TheWiringSaysNothing::class, '`detail`');
});

it('carries lemonfiber\'s value beside the operator\'s, and refuses a conflict without lemonfiber\'s', function (): void {
    $conflicted = HowAConnectionEnded::conflicted('/downloads', '/mnt/downloads');
    $wouldWire = HowAConnectionEnded::wouldWire('', '/mnt/old');

    expect([$conflicted->state(), $conflicted->said(), $conflicted->ours(), $conflicted->yours()])->toBe([WhereAConnectionStands::Conflicted, '', '/downloads', '/mnt/downloads'])
        ->and([$wouldWire->state(), $wouldWire->ours(), $wouldWire->yours()])->toBe([WhereAConnectionStands::WouldWire, '', '/mnt/old'])
        ->and(HowAConnectionEnded::conflicted('/downloads', '')->yours())->toBe('')
        ->and(static fn(): HowAConnectionEnded => HowAConnectionEnded::conflicted(' ', '/mnt/downloads'))->toThrow(TheWiringSaysNothing::class, '`ours`');
});

it('asks for a wiring run and a choice of filler by lemonfiber\'s names for them', function (): void {
    expect(WhatToDoAboutWiring::Wire->asked())->toBe('seed')
        ->and(WhatToDoAboutWiring::Fill->asked())->toBe('wiring-fill');
});
