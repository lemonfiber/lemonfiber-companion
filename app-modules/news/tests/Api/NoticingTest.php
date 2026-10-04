<?php

declare(strict_types=1);

namespace Modules\News\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AProblemNamed;
use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Kernel\Api\WhichTab;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\MarkingAsNew;
use Modules\News\Api\Noticing;
use Modules\News\Api\TheItems;
use Modules\News\Internal\NewsOfAStack;

use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;

/** The stack whose news is noticed. */
function theStackWhoseNewsIsNoticed(): StackId
{
    return StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
}

/** Another stack, whose news must never answer for the first's. */
function anotherStackWhoseNewsIsNoticed(): StackId
{
    return StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST)));
}

/** What is kept of each stack's news, over a seal and a store a test can see into. */
function newsOver(ASealInMemory $seal, NewsKeptInMemory $kept): NewsOfAStack
{
    return new NewsOfAStack($seal, $kept, FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)));
}

/** What notices news, over a seal and a store a test can see into. */
function noticingOver(ASealInMemory $seal, NewsKeptInMemory $kept): Noticing
{
    return new Noticing(newsOver($seal, $kept));
}

/** The requests a stack holds, newest first. */
function requestsNumbered(int ...$numbers): TheItems
{
    return TheItems::of(KindOfNews::Request, ...array_map(AnItem::aRequest(...), $numbers));
}

/**
 * What is new among them, by number.
 *
 * @return list<string>
 */
function newAmong(Noticing $noticing, TheItems $items, ?StackId $stack = null): array
{
    return array_map(
        static fn(AnItem $item): string => $item->named(),
        iterator_to_array($noticing->whatIsNewIn($stack ?? theStackWhoseNewsIsNoticed(), $items), preserve_keys: false),
    );
}

it('marks nothing on the first sight of a kind, and what arrives after it', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());

    expect(newAmong($noticing, requestsNumbered(5, 4)))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(7, 6, 5, 4)))->toBe(['7', '6']);
});

it('marks nothing on a first sight of a stack holding none, and the first one that arrives', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());

    expect(newAmong($noticing, requestsNumbered()))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(1)))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(2, 1)))->toBe(['2']);
});

it('keeps what one stack has seen apart from another', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    newAmong($noticing, requestsNumbered(5));
    newAmong($noticing, requestsNumbered(2), anotherStackWhoseNewsIsNoticed());

    expect(newAmong($noticing, requestsNumbered(6, 5)))->toBe(['6'])
        ->and(newAmong($noticing, requestsNumbered(6, 5), anotherStackWhoseNewsIsNoticed()))->toBe(['6', '5']);
});

it('sees an item and every older one with it, and leaves the newer ones new', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    newAmong($noticing, requestsNumbered(4));
    $now = requestsNumbered(8, 7, 6, 4);

    expect($noticing->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(7), $now))->toBeTrue()
        ->and(newAmong($noticing, $now))->toBe(['8']);
});

it('never lowers what was seen when an older item is opened', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    newAmong($noticing, requestsNumbered(4));
    $now = requestsNumbered(8, 7, 4);
    $noticing->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(8), $now);

    expect($noticing->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(7), $now))->toBeTrue()
        ->and(newAmong($noticing, requestsNumbered(9, 8, 7, 4)))->toBe(['9']);
});

it('sees an item where nothing was seen yet', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());

    $noticing->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(3), requestsNumbered(3));

    expect(newAmong($noticing, requestsNumbered(4, 3)))->toBe(['4']);
});

it('sees everything of a kind at once, and a kind holding nothing is seen already', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    newAmong($noticing, requestsNumbered(2));

    expect($noticing->sawThemAll(theStackWhoseNewsIsNoticed(), requestsNumbered(5, 4, 3, 2)))->toBeTrue()
        ->and(newAmong($noticing, requestsNumbered(5, 4, 3, 2)))->toBe([])
        ->and($noticing->sawThemAll(theStackWhoseNewsIsNoticed(), requestsNumbered()))->toBeTrue();
});

it('marks nothing of a kind switched off, and starts from what is current when it is switched on again', function (): void {
    $news = newsOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    $marking = new MarkingAsNew($news);
    $noticing = new Noticing($news);
    newAmong($noticing, requestsNumbered(2));

    $marking->markNoLonger(theStackWhoseNewsIsNoticed(), KindOfNews::Request);
    $whileOff = newAmong($noticing, requestsNumbered(4, 3, 2));
    $marking->mark(theStackWhoseNewsIsNoticed(), KindOfNews::Request);

    expect($whileOff)->toBe([])
        ->and(newAmong($noticing, requestsNumbered(4, 3, 2)))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(5, 4, 3, 2)))->toBe(['5']);
});

it('reads a marker that does not read as nothing seen, and starts again from what is current', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty();
    $noticing = noticingOver($seal, $kept);
    newAmong($noticing, requestsNumbered(2));
    $kept->holdsOneALaterBuildWrote($seal->stack(theStackWhoseNewsIsNoticed()));

    expect(newAmong($noticing, requestsNumbered(4, 3, 2)))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(5, 4, 3, 2)))->toBe(['5']);
});

it('says it was not kept where the store or the seal refuses', function (): void {
    expect(noticingOver(ASealInMemory::working(), NewsKeptInMemory::unreachable())->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(3), requestsNumbered(3)))->toBeFalse()
        ->and(noticingOver(ASealInMemory::withNoSecureStorage(), NewsKeptInMemory::empty())->sawIt(theStackWhoseNewsIsNoticed(), AnItem::aRequest(3), requestsNumbered(3)))->toBeFalse();
});

it('marks nothing it cannot keep a first sight of, so nothing floods in on the next', function (): void {
    $noticing = noticingOver(ASealInMemory::withNoSecureStorage(), NewsKeptInMemory::empty());

    expect(newAmong($noticing, requestsNumbered(2, 1)))->toBe([])
        ->and(newAmong($noticing, requestsNumbered(3, 2, 1)))->toBe([]);
});

it('forgets what it kept of a stack, and says whether it keeps anything of one', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    newAmong($noticing, requestsNumbered(2));

    expect($noticing->keepsAnythingOf(theStackWhoseNewsIsNoticed()))->toBeTrue()
        ->and($noticing->keepsAnythingOf(anotherStackWhoseNewsIsNoticed()))->toBeFalse()
        ->and($noticing->forgetTheStack(theStackWhoseNewsIsNoticed())->howMany())->toBe(1)
        ->and($noticing->keepsAnythingOf(theStackWhoseNewsIsNoticed()))->toBeFalse();
});

it('keeps something of a stack where only its choice of kinds, or a row it cannot read, is kept', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty();
    new MarkingAsNew(newsOver($seal, $kept))->markNoLonger(theStackWhoseNewsIsNoticed(), KindOfNews::Problem);
    $kept->holdsOneALaterBuildWrote($seal->stack(anotherStackWhoseNewsIsNoticed()));

    expect(noticingOver($seal, $kept)->keepsAnythingOf(theStackWhoseNewsIsNoticed()))->toBeTrue()
        ->and(noticingOver($seal, $kept)->keepsAnythingOf(anotherStackWhoseNewsIsNoticed()))->toBeTrue();
});

it('says it may keep something of a stack where nothing can be sealed to look', function (): void {
    expect(noticingOver(ASealInMemory::thatWillNotOpen(), NewsKeptInMemory::empty())->keepsAnythingOf(theStackWhoseNewsIsNoticed()))->toBeTrue();
});

/**
 * What a stack names as newest: these releases and problems, and a request numbered nine.
 *
 * @param list<string>             $releases
 * @param list<array{string, int}> $problems each check with its onset in seconds
 */
function theNewestAStackNames(array $releases, array $problems, bool $releasesRead = true): TheNewestNamed
{
    $named = [];

    foreach ($problems as [$check, $onset]) {
        $named[] = new AProblemNamed(Check::of($check), Instant::atEpochSeconds($onset));
    }

    return new TheNewestNamed(
        $releasesRead ? WhatTheStackListed::these(...array_map(AReleaseNamed::versioned(...), $releases)) : WhatTheStackListed::unread(),
        WhatTheStackListed::these(RequestId::numbered(9)),
        WhatTheStackListed::these(...$named),
    );
}

/**
 * How many new items each tab holds, by the tab's word.
 *
 * @return array<string, int>
 */
function howEachTabIsMarked(Noticing $noticing, TheNewestNamed $newest): array
{
    $marks = $noticing->whatTheTabsHold(theStackWhoseNewsIsNoticed(), $newest);
    $counted = [];

    foreach (WhichTab::cases() as $tab) {
        $counted[$tab->value] = $marks->howManyOn($tab);
    }

    return $counted;
}

it('marks no tab on the first reading of a stack, and then Updates and Health for what arrives after it', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());

    expect(howEachTabIsMarked($noticing, theNewestAStackNames(['2.3.0'], [['disk.space', 1_790_000_000]])))
        ->toBe(['health' => 0, 'services' => 0, 'updates' => 0, 'repairs' => 0])
        ->and(howEachTabIsMarked($noticing, theNewestAStackNames(['2.5.0', '2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600], ['disk.space', 1_790_000_000]])))
        ->toBe(['health' => 1, 'services' => 0, 'updates' => 2, 'repairs' => 0]);
});

it('marks no tab for a kind switched off, and still marks the other', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty();
    $noticing = noticingOver($seal, $kept);
    howEachTabIsMarked($noticing, theNewestAStackNames(['2.3.0'], [['disk.space', 1_790_000_000]]));
    new MarkingAsNew(newsOver($seal, $kept))->markNoLonger(theStackWhoseNewsIsNoticed(), KindOfNews::Problem);

    expect(howEachTabIsMarked($noticing, theNewestAStackNames(['2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600]])))
        ->toBe(['health' => 0, 'services' => 0, 'updates' => 1, 'repairs' => 0]);
});

it('marks no tab for a kind the stack could not read, and still marks the other', function (): void {
    $noticing = noticingOver(ASealInMemory::working(), NewsKeptInMemory::empty());
    howEachTabIsMarked($noticing, theNewestAStackNames(['2.3.0'], [['disk.space', 1_790_000_000]]));

    expect(howEachTabIsMarked($noticing, theNewestAStackNames(['2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600]], releasesRead: false)))
        ->toBe(['health' => 1, 'services' => 0, 'updates' => 0, 'repairs' => 0]);
});
