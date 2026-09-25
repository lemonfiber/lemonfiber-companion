<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AMomentInItsHistory;
use Modules\Kernel\Api\AnEpisodeNotHereYet;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\AStageItReached;
use Modules\Kernel\Api\HowFarItGot;
use Modules\Kernel\Api\HowMuchOfASeasonIsHere;
use Modules\Kernel\Api\HowMuchOfItIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\TheEpisodesNotHereYet;
use Modules\Kernel\Api\TheMomentsInItsHistory;
use Modules\Kernel\Api\TheSeasonsOfIt;
use Modules\Kernel\Api\TheStagesItReached;
use Modules\Kernel\Api\TheTraceSaysNothing;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhatWasFoundOfTheTrace;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Kernel\Api\WhereTheServicesDisagree;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichTraceArm
{
    public function __construct(public string $said) {}
}

/** A trace found, with a whole item and nothing else. */
function aPlainTrace(): WhatTheTraceFound
{
    return WhatTheTraceFound::traced(
        HowSureTheTraceIs::Certain,
        HowFarItGot::reached(Stage::Available, TheStagesItReached::of(), ''),
        TheMomentsInItsHistory::of(),
        WhereTheServicesDisagree::of(),
        HowMuchOfItIsHere::aWholeItem(),
    );
}

it('tells nothing asked for from an item followed, and keeps what was followed', function (): void {
    $fold = static fn(WhereItGotTo $trace): string => $trace->either(
        nothingAskedFor: static fn(): WhichTraceArm => new WhichTraceArm(sprintf('nothing:%s', $trace->item())),
        followed: static fn(WhatTheTraceFound $found): WhichTraceArm => new WhichTraceArm(sprintf('followed:%s:%s', $trace->item(), $found->got()->furthest()->value)),
    )->said;

    expect($fold(WhereItGotTo::nothingAskedFor('Dune')))->toBe('nothing:Dune')
        ->and($fold(WhereItGotTo::followed('Dune', aPlainTrace())))->toBe('followed:Dune:available')
        ->and(fn(): WhereItGotTo => WhereItGotTo::nothingAskedFor(' '))->toThrow(TheTraceSaysNothing::class, '`item`');
});

it('keeps everything a trace found', function (): void {
    $stage = AStageItReached::recorded(Stage::Grabbed, ServiceId::called('sonarr'), '2026-09-20T10:00:00Z');
    $moment = AMomentInItsHistory::recorded(WhatHappenedToIt::DownloadFailed, '2026-09-19');
    $found = WhatTheTraceFound::traced(
        HowSureTheTraceIs::Uncertain,
        HowFarItGot::reached(Stage::Grabbed, TheStagesItReached::of($stage), 'No peers'),
        TheMomentsInItsHistory::of($moment),
        WhereTheServicesDisagree::of('Jellyfin holds what nobody watches'),
        HowMuchOfItIsHere::aWholeItem(),
    );

    expect([$found->sure(), $found->got()->furthest(), $found->got()->stall(), iterator_to_array($found->got()->stages(), preserve_keys: true), iterator_to_array($found->history(), preserve_keys: true), iterator_to_array($found->disagreements(), preserve_keys: true)])
        ->toBe([HowSureTheTraceIs::Uncertain, Stage::Grabbed, 'No peers', [$stage], [$moment], ['Jellyfin holds what nobody watches']])
        ->and([$stage->stage(), $stage->service()->named(), $stage->at(), $moment->happened(), $moment->at()])
        ->toBe([Stage::Grabbed, 'sonarr', '2026-09-20T10:00:00Z', WhatHappenedToIt::DownloadFailed, '2026-09-19']);
});

it('counts a series by season and tells it from a whole item', function (): void {
    $episode = AnEpisodeNotHereYet::numbered(1, 9, 'The We We Are', Stage::Downloading);
    $season = HowMuchOfASeasonIsHere::counted(1, 8, 9, 1, $episode);
    $series = ASeriesCounted::counted(8, 9, 1, $season);
    $fold = static fn(HowMuchOfItIsHere $here): string => $here->either(
        whole: static fn(): WhichTraceArm => new WhichTraceArm('whole'),
        inParts: static fn(ASeriesCounted $counted): WhichTraceArm => new WhichTraceArm(sprintf('%d/%d+%d', $counted->have(), $counted->wanted(), $counted->unmonitored())),
    )->said;

    expect($fold(HowMuchOfItIsHere::aWholeItem()))->toBe('whole')
        ->and($fold(HowMuchOfItIsHere::inParts($series)))->toBe('8/9+1')
        ->and(iterator_to_array($series->seasons(), preserve_keys: true))->toBe([$season])
        ->and([$season->season(), $season->have(), $season->wanted(), $season->unmonitored(), iterator_to_array($season->outstanding(), preserve_keys: true)])->toBe([1, 8, 9, 1, [$episode]])
        ->and([$episode->season(), $episode->number(), $episode->title(), $episode->stage()])->toBe([1, 9, 'The We We Are', Stage::Downloading]);
});

it('refuses counts and sentences that cannot be', function (callable $build, string $field): void {
    expect($build)->toThrow(TheTraceSaysNothing::class, sprintf('`%s`', $field));
})->with([
    'a blank time on a stage' => [static fn(): AStageItReached => AStageItReached::recorded(Stage::Grabbed, ServiceId::called('sonarr'), ' '), 'at'],
    'no time on a moment' => [static fn(): AMomentInItsHistory => AMomentInItsHistory::recorded(WhatHappenedToIt::Grabbed, ''), 'at'],
    'a negative episode' => [static fn(): AnEpisodeNotHereYet => AnEpisodeNotHereYet::numbered(1, -1, 'Pilot', Stage::Searching), 'number'],
    'a negative season of an episode' => [static fn(): AnEpisodeNotHereYet => AnEpisodeNotHereYet::numbered(-1, 1, 'Pilot', Stage::Searching), 'number'],
    'an untitled episode' => [static fn(): AnEpisodeNotHereYet => AnEpisodeNotHereYet::numbered(1, 1, ' ', Stage::Searching), 'title'],
    'a negative season' => [static fn(): HowMuchOfASeasonIsHere => HowMuchOfASeasonIsHere::counted(-1, 0, 0, 0), 'season'],
    'less than nothing here of a season' => [static fn(): HowMuchOfASeasonIsHere => HowMuchOfASeasonIsHere::counted(1, -1, 0, 0), 'season'],
    'more of a season here than wanted' => [static fn(): HowMuchOfASeasonIsHere => HowMuchOfASeasonIsHere::counted(1, 3, 2, 0), 'season'],
    'less than nobody unasked in a season' => [static fn(): HowMuchOfASeasonIsHere => HowMuchOfASeasonIsHere::counted(1, 0, 0, -1), 'season'],
    'less than nothing here of a series' => [static fn(): ASeriesCounted => ASeriesCounted::counted(-1, 0, 0), 'coverage'],
    'more of a series here than wanted' => [static fn(): ASeriesCounted => ASeriesCounted::counted(3, 2, 0), 'coverage'],
    'less than nobody unasked in a series' => [static fn(): ASeriesCounted => ASeriesCounted::counted(0, 0, -1), 'coverage'],
    'a blank reason it stopped' => [static fn(): HowFarItGot => HowFarItGot::reached(Stage::Grabbed, TheStagesItReached::of(), ' '), 'stall'],
    'a blank disagreement' => [static fn(): WhereTheServicesDisagree => WhereTheServicesDisagree::of('a', ' '), 'findings'],
    'nothing to follow' => [static fn(): WhatToFollow => WhatToFollow::called(' '), 'term'],
]);

it('takes a count at its edge, and a whole season here', function (): void {
    expect(HowMuchOfASeasonIsHere::counted(0, 2, 2, 0)->have())->toBe(2)
        ->and(ASeriesCounted::counted(0, 0, 0)->wanted())->toBe(0)
        ->and(AnEpisodeNotHereYet::numbered(0, 0, 'Special', Stage::Monitored)->number())->toBe(0)
        ->and(AStageItReached::recorded(Stage::Monitored, ServiceId::called('sonarr'), '')->at())->toBe('');
});

it('follows a term trimmed', function (): void {
    expect(WhatToFollow::called('  Dune ')->term())->toBe('Dune');
});

it('keeps each list in order and as a list however it is handed', function (): void {
    $a = AnEpisodeNotHereYet::numbered(1, 1, 'A', Stage::Searching);
    $b = AnEpisodeNotHereYet::numbered(1, 2, 'B', Stage::Searching);
    $season = HowMuchOfASeasonIsHere::counted(1, 0, 2, 0);

    expect(iterator_to_array(TheEpisodesNotHereYet::of(...['x' => $b, 'y' => $a]), preserve_keys: true))->toBe([$b, $a])
        ->and(TheEpisodesNotHereYet::of($a, $b))->toHaveCount(2)
        ->and(iterator_to_array(TheSeasonsOfIt::of(...['x' => $season]), preserve_keys: true))->toBe([$season])
        ->and(TheSeasonsOfIt::of($season))->toHaveCount(1)
        ->and(iterator_to_array(TheStagesItReached::of(...['x' => AStageItReached::recorded(Stage::Found, ServiceId::called('radarr'), '')]), preserve_keys: true))->toHaveKeys([0])
        ->and(iterator_to_array(TheMomentsInItsHistory::of(...['x' => AMomentInItsHistory::recorded(WhatHappenedToIt::Removed, 'now')]), preserve_keys: true))->toHaveKeys([0])
        ->and(iterator_to_array(WhereTheServicesDisagree::of(...['x' => 'one']), preserve_keys: true))->toBe(['one']);
});

it('names a catalogue key for every case', function (): void {
    expect(HowSureTheTraceIs::Uncertain->saidOnTheScreen())->toBe('health.trace.confidence.uncertain')
        ->and(WhatHappenedToIt::DownloadFailed->saidOnTheScreen())->toBe('health.trace.outcome.download-failed');
});

it('takes the arm for what came back', function (): void {
    $fold = static fn(WhatWasFoundOfTheTrace $answer): string => $answer->either(
        found: static fn(WhereItGotTo $trace): WhichTraceArm => new WhichTraceArm(sprintf('found:%s', $trace->item())),
        met: static fn(Obstacle $why): WhichTraceArm => new WhichTraceArm(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundOfTheTrace::found(WhereItGotTo::nothingAskedFor('Dune'))))->toBe('found:Dune')
        ->and($fold(WhatWasFoundOfTheTrace::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
