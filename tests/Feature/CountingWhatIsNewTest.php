<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AProblemNamed;
use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\Noticing;
use Modules\News\Api\TheItems;
use Modules\News\Internal\NewsOfAStack;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Tests\Support\AroundThePhone;
use Tests\Support\AScreenListening;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The menu counts how much is new on the stack beside What's new: updates,
// problems and requests, from what the stack names as newest on its stream,
// against what the operator has seen. The digits are drawn between the label
// and the chevron, as a tab's badge is, and a screen reader hears the count
// with the label. Nothing new, no count.

/** The stack whose menu counts what is new. Named for this file (`G10`). */
function theStackWhoseNewIsCounted(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The barn'),
        Address::of('https://192.168.1.45:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the barn names as newest: these releases, requests and checks found wrong.
 *
 * @param list<string>             $releases
 * @param list<int>                $requests
 * @param list<array{string, int}> $problems each check with its onset in seconds
 */
function whatTheBarnNamesAsNewest(array $releases, array $requests, array $problems): WhatWasHeard
{
    $named = [];

    foreach ($problems as [$check, $onset]) {
        $named[] = new AProblemNamed(Check::of($check), Instant::atEpochSeconds($onset));
    }

    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))->naming(new TheNewestNamed(
        WhatTheStackListed::these(...array_map(AReleaseNamed::versioned(...), $releases)),
        WhatTheStackListed::these(...array_map(RequestId::numbered(...), $requests)),
        WhatTheStackListed::these(...$named),
    ));
}

/** The barn's stream: first what it held, then one new release, two new requests and one new problem. */
function theBarnSayingWhatIsNew(): AStackThatSpeaksUp
{
    return AStackThatSpeaksUp::holdingOpen(
        whatTheBarnNamesAsNewest(['2.3.0'], [9], [['disk.space', 1_790_000_000]]),
        whatTheBarnNamesAsNewest(['2.4.0', '2.3.0'], [11, 10, 9], [['vpn.leak', 1_790_000_600], ['disk.space', 1_790_000_000]]),
    );
}

/** A screen about the barn, as the operator, hearing `$stream` on this clock, noticing news through `$noticing`: Updates, or the words the menu opens. */
function aBarnScreenHearing(string $which, AStackThatSpeaksUp $stream, FrozenClock $clock, ?Noticing $noticing = null): HowCurrentThisStackIs|WhatTheWordsMean
{
    $stack = theStackWhoseNewIsCounted();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $around = AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain);
    $listening = AroundThePhone::listening($stream, $clock, noticing: $noticing);
    $offline = Obstacle::of(KindOfObstacle::DeviceHasNoNetwork);

    $screen = $which === 'updates'
        ? new HowCurrentThisStackIs(AStackThatKeepsCurrent::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, WhatThePhoneKeeps::noUpkeepYet())
        : new WhatTheWordsMean(AStackThatExplainsItsWords::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening);
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * The What's new row the menu draws: the count it carries, and what a screen reader hears.
 *
 * @return array{badge: mixed, said: mixed}
 */
function theWhatsNewRow(HowCurrentThisStackIs|WhatTheWordsMean $screen): array
{
    foreach (theListItemsIn(WhatTheDeviceWouldDraw::menuTree($screen, $screen->drawerOverride())) as $row) {
        if (data_get($row, 'props.headline') === __('navigation.menu.whats_new')) {
            return ['badge' => data_get($row, 'props.badge'), 'said' => data_get($row, 'props.a11y_label')];
        }
    }

    throw new RuntimeException('The menu draws no What\'s new row.');
}

/**
 * The badge each marked tab carries on the bar a screen draws, Health's and Updates'.
 *
 * @return array{health: string, updates: string}
 */
function theMarksOnTheBarOf(HowCurrentThisStackIs|WhatTheWordsMean $screen): array
{
    $marks = $screen->marks();

    return ['health' => $marks->health->badge, 'updates' => $marks->updates->badge];
}

/**
 * Every list row a drawn tree holds, wherever it sits.
 *
 * @return list<mixed>
 */
function theListItemsIn(mixed $node): array
{
    if (! is_array($node)) {
        return [];
    }

    $found = ($node['type'] ?? null) === 'list_item' ? [$node] : [];

    foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
        $found = [...$found, ...theListItemsIn($child)];
    }

    return $found;
}

it('counts what is new of every kind beside What\'s new, and says it with the label', function (string $which): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $screen = aBarnScreenHearing($which, theBarnSayingWhatIsNew(), $clock);

    $screen->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $screen->listen();

    expect(theWhatsNewRow($screen))->toBe([
        'badge' => '4',
        'said' => trans_choice('news.new_on_tab', 4, ['tab' => __('navigation.menu.whats_new')]),
    ])
        ->and(trans_choice('news.new_on_tab', 4, ['tab' => __('navigation.menu.whats_new')]))->toBe('What\'s new, 4 new')
        ->and(trans_choice('news.new_on_tab', 3, ['tab' => __('navigation.menu.whats_new', locale: 'nl')], 'nl'))->toBe('Wat is nieuw, 3 nieuw');
})->with(['updates', 'the words the menu opens']);

it('draws no count beside What\'s new where nothing is new, as on the first reading', function (): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $screen = aBarnScreenHearing('updates', theBarnSayingWhatIsNew(), $clock);
    $before = theWhatsNewRow($screen);

    $screen->listen();

    expect($before)->toBe(['badge' => null, 'said' => __('navigation.menu.whats_new')])
        ->and(theWhatsNewRow($screen))->toBe(['badge' => null, 'said' => __('navigation.menu.whats_new')]);
});

it('draws the marks and the count an earlier screen heard on a screen that opens, before it hears anything itself', function (): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $noticing = NoticingWhatIsNew::fromNothing();
    $earlier = aBarnScreenHearing('updates', theBarnSayingWhatIsNew(), $clock, $noticing);
    $earlier->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $earlier->listen();

    $opened = aBarnScreenHearing('the words the menu opens', AStackThatSpeaksUp::holdingOpen(), $clock, $noticing);

    expect(theWhatsNewRow($opened)['badge'])->toBe('4')
        ->and(theMarksOnTheBarOf($opened))->toBe(['health' => '1', 'updates' => '1']);
});

it('draws what the stack names next in place of what an earlier screen heard', function (): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $noticing = NoticingWhatIsNew::fromNothing();
    $earlier = aBarnScreenHearing('updates', theBarnSayingWhatIsNew(), $clock, $noticing);
    $earlier->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $earlier->listen();
    $opened = aBarnScreenHearing('the words the menu opens', AStackThatSpeaksUp::holdingOpen(
        whatTheBarnNamesAsNewest(['2.5.0', '2.4.0', '2.3.0'], [11, 10, 9], [['disk.space', 1_790_000_000]]),
    ), $clock, $noticing);

    $opened->listen();

    expect(theWhatsNewRow($opened)['badge'])->toBe('4')
        ->and(theMarksOnTheBarOf($opened))->toBe(['health' => '', 'updates' => '2']);
});

it('draws no count an earlier screen heard once the operator has seen it all', function (): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $noticing = NoticingWhatIsNew::fromNothing();
    $earlier = aBarnScreenHearing('updates', theBarnSayingWhatIsNew(), $clock, $noticing);
    $earlier->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $earlier->listen();
    $stack = theStackWhoseNewIsCounted()->id();

    $noticing->sawThemAll($stack, TheItems::of(KindOfNews::Update, AnItem::anUpdate('2.4.0'), AnItem::anUpdate('2.3.0')));
    $noticing->sawThemAll($stack, TheItems::of(KindOfNews::Request, AnItem::aRequest(11), AnItem::aRequest(10), AnItem::aRequest(9)));
    $noticing->sawThemAll($stack, TheItems::of(KindOfNews::Problem, AnItem::aProblem('vpn.leak', Instant::atEpochSeconds(1_790_000_600))));

    expect(theWhatsNewRow($earlier)['badge'])->toBeNull()
        ->and(theMarksOnTheBarOf(aBarnScreenHearing('the words the menu opens', AStackThatSpeaksUp::holdingOpen(), $clock, $noticing)))->toBe(['health' => '', 'updates' => '']);
});

it('hands every screen the container builds the same record of what each stack last named', function (): void {
    $lastNamed = static fn(): mixed => new ReflectionProperty(NewsOfAStack::class, 'lastNamed')->getValue(app(NewsOfAStack::class));

    expect($lastNamed())->toBeInstanceOf(WhatEachStackLastNamed::class)
        ->and($lastNamed())->toBe($lastNamed())
        ->and($lastNamed())->toBe(app(WhatEachStackLastNamed::class));
});

it('opens What\'s new on the stack whose menu opened it', function (): void {
    $screen = aBarnScreenHearing('updates', AStackThatSpeaksUp::holdingOpen(), FrozenClock::at(AScreenListening::secondsAfterOpening(0)));

    $screen->openWhatsNew();

    expect($screen->getNavigationIntent()?->uri)->toBe(AScreenWithoutAStack::WhatsNew->value)
        ->and($screen->getNavigationIntent()?->data)->toBe([AScreenWithoutAStack::WHATS_NEW_SHOWS => theStackWhoseNewIsCounted()->id()->stored()]);
});

it('draws the count between the label and the chevron on each platform, and leaves it to the label to say', function (string $file, string ...$carries): void {
    // The renderers are the package's, rewritten by `scripts/patch_nativephp.php`;
    // a count the row carries and no renderer draws is a menu with no count.
    foreach ($carries as $line) {
        expect((string) file_get_contents(base_path($file)))->toContain($line);
    }
})->with([
    'the row' => [
        'vendor/nativephp/mobile-ui/src/Elements/ListItem.php',
        '$this->listItemProps[\'badge\'] = (string) $attrs[\'badge\'];',
    ],
    'Android' => [
        'vendor/nativephp/mobile-ui/resources/android/ListItemRenderer.kt',
        'trailingContent = withBadge(p.getString("badge", ""), run {',
        'Badge { Text(badge, fontFamily = nuiDefaultFontFamily(), modifier = Modifier.clearAndSetSemantics {}) }',
        'trailing?.invoke()',
    ],
    'iOS' => [
        'vendor/nativephp/mobile-ui/resources/ios/NativeUIListItemRenderer.swift',
        'let badge = p.getString("badge", default: "")',
        '.background(Capsule().fill(theme.destructive))',
    ],
]);
