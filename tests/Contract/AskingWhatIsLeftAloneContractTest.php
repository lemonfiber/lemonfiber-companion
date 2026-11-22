<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnAreaLeftAlone;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\AskingWhatIsLeftAlone;
use Modules\Kernel\Api\AThingLeftAlone;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatAnAreaCovers;
use Modules\Kernel\Api\WhatIsLeftAlone;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatIsLeftAlone;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** Two areas the loft was told to leave alone: one covering a thing of every kind and more besides, one covering nothing. */
function theSameAreasLeftAlone(): TheAreasLeftAlone
{
    return TheAreasLeftAlone::these(
        3,
        AnAreaLeftAlone::declared(' recyclarr ', areaCut: true, why: ' my own profiles live in here ', whyCut: false, covers: WhatAnAreaCovers::these(
            5,
            AThingLeftAlone::of(WhatIsLeftAlone::Service, 'recyclarr'),
            AThingLeftAlone::of(WhatIsLeftAlone::Path, 'config/recyclarr/profiles.yml'),
            AThingLeftAlone::of(WhatIsLeftAlone::Setting, 'RECYCLARR_CRON'),
        )),
        AnAreaLeftAlone::declared('sonar', areaCut: false, why: 'tuned by hand', whyCut: true, covers: WhatAnAreaCovers::these(0)),
    );
}

/**
 * A `config` answer holding the settings and, beside them, these fields about what is left alone.
 *
 * @param  array<string, mixed>  $leftAlone
 * @return array<string, mixed>
 */
function aConfigAnswerWith(array $leftAlone): array
{
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => false,
            'decisions' => [],
            'settings' => [['key' => 'LEMONFIBER_UNMANAGED', 'value' => 'recyclarr=my own profiles live in here,sonar=tuned by hand', 'secret' => false, 'origin' => ['origin' => 'operator']]],
            ...$leftAlone,
        ],
    ];
}

/**
 * The body a stack sends for those areas.
 *
 * @param  list<mixed>  $unmanaged
 * @return array<string, mixed>
 */
function whatAStackSendsAboutWhatItLeavesAlone(array $unmanaged = [
    ['area' => ' recyclarr ', 'area_cut' => true, 'why' => ' my own profiles live in here ', 'why_cut' => false, 'covers' => [
        ['kind' => 'service', 'name' => 'recyclarr'],
        ['kind' => 'path', 'name' => 'config/recyclarr/profiles.yml'],
        ['kind' => 'setting', 'name' => 'RECYCLARR_CRON'],
    ], 'covers_more' => 5],
    ['area' => 'sonar', 'area_cut' => false, 'why' => 'tuned by hand', 'why_cut' => true, 'covers' => [], 'covers_more' => 0],
], int $more = 3): array
{
    return aConfigAnswerWith(['unmanaged' => $unmanaged, 'unmanaged_more' => $more]);
}

/** @param array<string, mixed> $body */
function theLeftAloneAdapterAnswering(array $body, int $status = 200): Arrangements
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make($body, $status)]);

    return new Arrangements(new PinnedClients());
}

/** What was found of the areas, as one string, whichever arm it took. */
function whatWasFoundOfTheAreasAsText(WhatWasFoundOfTheAreasLeftAlone $found): string
{
    return $found->either(
        listed: static function (TheAreasLeftAlone $areas): TheWordCarriedOut {
            $rows = [];

            foreach ($areas as $area) {
                $covers = [];

                foreach ($area->covers as $thing) {
                    $covers[] = sprintf('%s:%s', $thing->kind->value, $thing->name);
                }

                $rows[] = sprintf('[%s|%s|%s|%s|%s+%d]', $area->area, $area->areaCut ? 'cut' : 'whole', $area->why, $area->whyCut ? 'cut' : 'whole', implode(';', $covers), $area->covers->more());
            }

            return new TheWordCarriedOut(sprintf('listed:%s+%d', implode(',', $rows), $areas->more()));
        },
        notCarried: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not carried'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

/** What a stack answered when asked what it leaves alone. */
function howWhatIsLeftAloneReadsAsText(AskingWhatIsLeftAlone $asking): string
{
    return whatWasFoundOfTheAreasAsText($asking->leftAloneOn(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret')));
}

/** What the same answer said the stack leaves alone, read beside its settings. */
function howWhatIsLeftAloneReadsBesideTheSettings(Arranging $arranging): string
{
    return $arranging->asItStands(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret'))->either(
        told: static fn(Settings $set, WhatWasFoundOfTheAreasLeftAlone $leftAlone): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d settings, %s', count($set), whatWasFoundOfTheAreasAsText($leftAlone))),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->kind()->value)),
    )->said;
}

it('the fake and the adapter read the same areas, in the order declared, each covered thing with its own kind', function (): void {
    $said = 'listed:[ recyclarr |cut| my own profiles live in here |whole|service:recyclarr;path:config/recyclarr/profiles.yml;setting:RECYCLARR_CRON+5],'
        . '[sonar|whole|tuned by hand|cut|+0]+3';

    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone())))->toBe($said)
        ->and(howWhatIsLeftAloneReadsAsText(AStackThatSaysWhatIsLeftAlone::with(theSameAreasLeftAlone())))->toBe($said);
});

it('reads the same areas beside the settings, from the one answer', function (): void {
    expect(howWhatIsLeftAloneReadsBesideTheSettings(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone([], 0))))->toBe('1 settings, listed:+0');
});

it('tells a stack with nothing declared from one too old to say', function (): void {
    $older = aConfigAnswerWith([]);

    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone([], 0))))->toBe('listed:+0')
        ->and(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering($older)))->toBe('not carried')
        ->and(howWhatIsLeftAloneReadsBesideTheSettings(theLeftAloneAdapterAnswering($older)))->toBe('1 settings, not carried')
        ->and(howWhatIsLeftAloneReadsAsText(AStackThatSaysWhatIsLeftAlone::tooOldToSay()))->toBe('not carried');
});

it('the fake and the adapter both say what stood in the way rather than answer with nothing', function (): void {
    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(['nothing' => 'the contract knows'], 500)))->toStartWith('met:')
        ->and(howWhatIsLeftAloneReadsAsText(AStackThatSaysWhatIsLeftAlone::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met:no_answer');
});

it('says a member is refused the list, as the stack refuses them the read', function (): void {
    $refused = ['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'forbidden', 'message' => 'This read is the operator\'s.']];

    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering($refused, 403)))->toStartWith('met:')
        ->and(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering($refused, 403)))->not->toBe('not carried');
});

it('refuses an area it cannot read rather than leaving it off the list, and keeps the settings beside it', function (mixed $area): void {
    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone([$area], 0))))->toBe('met:answer_unreadable')
        ->and(howWhatIsLeftAloneReadsBesideTheSettings(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone([$area], 0))))->toBe('1 settings, met:answer_unreadable');
})->with([
    'not a table' => ['recyclarr'],
    'a blank area' => [['area' => ' ', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => [], 'covers_more' => 0]],
    'no reason' => [['area' => 'a', 'area_cut' => false, 'why_cut' => false, 'covers' => [], 'covers_more' => 0]],
    'cut as text' => [['area' => 'a', 'area_cut' => 'false', 'why' => 'x', 'why_cut' => false, 'covers' => [], 'covers_more' => 0]],
    'no reason cut' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'covers' => [], 'covers_more' => 0]],
    'covers not a list' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => 'recyclarr', 'covers_more' => 0]],
    'a kind outside the contract' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => [['kind' => 'volume', 'name' => 'n']], 'covers_more' => 0]],
    'a covered thing with no name' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => [['kind' => 'path', 'name' => '']], 'covers_more' => 0]],
    'a covered thing that is not a table' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => ['path'], 'covers_more' => 0]],
    'more below none' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => [], 'covers_more' => -1]],
    'no more' => [['area' => 'a', 'area_cut' => false, 'why' => 'x', 'why_cut' => false, 'covers' => []]],
]);

it('refuses a list that is not one, a count of the rest below none or missing, and an answer that is not a table', function (): void {
    $notAList = aConfigAnswerWith(['unmanaged' => 'recyclarr=mine', 'unmanaged_more' => 0]);
    $noCount = aConfigAnswerWith(['unmanaged' => []]);

    expect(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering($notAList)))->toBe('met:answer_unreadable')
        ->and(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(whatAStackSendsAboutWhatItLeavesAlone(more: -1))))->toBe('met:answer_unreadable')
        ->and(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering($noCount)))->toBe('met:answer_unreadable')
        ->and(howWhatIsLeftAloneReadsAsText(theLeftAloneAdapterAnswering(['api_version' => 1, 'kind' => 'config', 'data' => 'unmanaged'])))->toBe('met:answer_unreadable');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ConfigEnvelope', whatAStackSendsAboutWhatItLeavesAlone()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
