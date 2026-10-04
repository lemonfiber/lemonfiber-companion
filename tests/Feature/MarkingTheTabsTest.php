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
use Modules\News\Api\KindOfNews;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WatchingOneArrive;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Wayfinding\Api\TheTabs;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\AroundThePhone;
use Tests\Support\AScreenListening;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AServiceThatSpoke;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\AStackThatNarrates;
use Tests\Support\Fakes\AStackThatRehearses;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\AStackThatWouldMend;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// A tab holding something new carries a mark: the count, drawn as text in the
// badge, and a sentence a screen reader says with the tab's name. The newest of
// each kind arrives on the stack's stream, and the news module answers which
// tabs it marks against what the operator has seen. The first reading marks
// nothing, and a kind switched off marks nothing.

/** The stack whose tabs are marked. */
function theStackWhoseTabsAreMarked(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('m', Nonce::SHORTEST))),
        StackName::of('The shed'),
        Address::of('https://192.168.1.44:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the stack names as newest: these releases and these checks found wrong, each with its onset.
 *
 * @param list<string>             $releases
 * @param list<array{string, int}> $problems
 */
function whatTheShedNamesAsNewest(array $releases, array $problems): WhatWasHeard
{
    $named = [];

    foreach ($problems as [$check, $onset]) {
        $named[] = new AProblemNamed(Check::of($check), Instant::atEpochSeconds($onset));
    }

    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))->naming(new TheNewestNamed(
        WhatTheStackListed::these(...array_map(AReleaseNamed::versioned(...), $releases)),
        WhatTheStackListed::these(),
        WhatTheStackListed::these(...$named),
    ));
}

/** The Health tab's screen, hearing what the stream is scripted to say, over what the phone kept of what is new. */
function theShedsHealthHearing(AStackThatSpeaksUp $stream, ?ASealInMemory $seal = null, ?NewsKeptInMemory $kept = null): AScreenListening
{
    $stack = theStackWhoseTabsAreMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $window = ACaptureInMemory::inFront();
    $standings = StandingsInMemory::working();

    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($stream, $clock, $window, $standings),
        NoticingWhatIsNew::over($seal ?? ASealInMemory::working(), $kept ?? NewsKeptInMemory::empty()),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return new AScreenListening($screen, $stream, $clock, $window, $keychain, $standings);
}

/** A tab's screen on the shed, hearing what the stream is scripted to say, on this clock. */
function theShedsTabHearing(TheTabs $tab, AStackThatSpeaksUp $stream, FrozenClock $clock): HowThisStackIs|WhatThisStackRuns|HowCurrentThisStackIs|WhatWouldBePutRight
{
    $stack = theStackWhoseTabsAreMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $around = AroundThePhone::holding(StacksInMemory::holding($stack));
    $listening = AroundThePhone::listening($stream, $clock);
    $noticing = NoticingWhatIsNew::fromNothing();
    $offline = Obstacle::of(KindOfObstacle::DeviceHasNoNetwork);

    $screen = match ($tab) {
        TheTabs::Health => new HowThisStackIs(AStackThatWasAsked::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, $noticing),
        TheTabs::Services => new WhatThisStackRuns(AStackThatSupervises::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, $noticing),
        TheTabs::Updates => new HowCurrentThisStackIs(AStackThatKeepsCurrent::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, $noticing, WhatThePhoneKeeps::noUpkeepYet()),
        TheTabs::Repairs => new WhatWouldBePutRight(AStackThatWouldMend::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, $noticing),
    };
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * A screen on the shed that is no tab's own, hearing what the stream is scripted
 * to say, on this clock, and following a walk and a start on these.
 */
function theShedsOtherScreenHearing(
    string $which,
    AStackThatSpeaksUp $stream,
    FrozenClock $clock,
    ?AStackThatNarrates $walk = null,
    ?AStackThatSaysWhatItWaitsOn $start = null,
): WhatToDoWithThis|WhatThisServiceSaid|WhatTheWordsMean|WatchingOneArrive {
    $stack = theStackWhoseTabsAreMarked();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $around = AroundThePhone::holding(StacksInMemory::holding($stack));
    $listening = AroundThePhone::listening($stream, $clock);
    $noticing = NoticingWhatIsNew::fromNothing();
    $offline = Obstacle::of(KindOfObstacle::DeviceHasNoNetwork);

    $screen = match ($which) {
        'what to do with a service' => new WhatToDoWithThis(AStackThatSupervises::met($offline), AStackThatRehearses::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $start ?? AStackThatSaysWhatItWaitsOn::saying(), $listening, $noticing),
        'what a service said' => new WhatThisServiceSaid(AServiceThatSpoke::met($offline), $keychain, $around, AZoneThatIsSet::to('Europe/Amsterdam'), new AppsSettingsThatOpen(), $listening, $noticing),
        'the words the menu opens' => new WhatTheWordsMean(AStackThatExplainsItsWords::met($offline), $keychain, $around, new AppsSettingsThatOpen(), $listening, $noticing),
        default => new WatchingOneArrive(
            AStackThatWalksThrough::met($offline),
            AStackThatExplainsItsWords::met($offline),
            $keychain,
            $around,
            WorkLeftRunningInMemory::working(),
            $walk ?? AStackThatNarrates::holdingOpen(),
            $clock,
            ACaptureInMemory::inFront(),
            new AppsSettingsThatOpen(),
            $listening,
            $noticing,
        ),
    };
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => 'sonarr']);

    return $screen;
}

/**
 * Every node a drawn tree holds of one type, wherever it sits.
 *
 * @return list<array<mixed>>
 */
function theNodesOfType(mixed $node, string $type): array
{
    if (! is_array($node)) {
        return [];
    }

    $found = ($node['type'] ?? null) === $type ? [$node] : [];

    foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
        $found = [...$found, ...theNodesOfType($child, $type)];
    }

    return $found;
}

/**
 * One prop of a drawn node, where it is text, or empty.
 *
 * @param array<mixed> $node
 */
function theTextOf(array $node, string $prop): string
{
    $props = is_array($node['props'] ?? null) ? $node['props'] : [];

    return is_string($props[$prop] ?? null) ? $props[$prop] : '';
}

/**
 * Each tab in the bar the screen draws, by its id, with the badge it carries and what it says of it.
 *
 * @return array<string, array{badge: string, said: string}>
 */
function theMarksOnTheBar(NativeComponent $screen): array
{
    $tabs = [];

    foreach (theNodesOfType(WhatTheDeviceWouldDraw::tree($screen), 'bottom_nav_item') as $tab) {
        $tabs[theTextOf($tab, 'id')] = ['badge' => theTextOf($tab, 'badge'), 'said' => theTextOf($tab, 'badge_label')];
    }

    ksort($tabs);

    return $tabs;
}

/**
 * No tab carries a mark.
 *
 * @return array<string, array{badge: string, said: string}>
 */
function noTabIsMarked(): array
{
    return [
        'health' => ['badge' => '', 'said' => ''],
        'repairs' => ['badge' => '', 'said' => ''],
        'services' => ['badge' => '', 'said' => ''],
        'updates' => ['badge' => '', 'said' => ''],
    ];
}

it('marks no tab on the first reading of a stack', function (): void {
    $listening = theShedsHealthHearing(AStackThatSpeaksUp::holdingOpen(whatTheShedNamesAsNewest(['2.3.0'], [['disk.space', 1_790_000_000]])));
    $listening->wakesAt(0);

    expect(theMarksOnTheBar($listening->screen))->toBe(noTabIsMarked());
});

it('marks Health and Updates with how many new items each holds, as text and a sentence a screen reader says', function (): void {
    $listening = theShedsHealthHearing(AStackThatSpeaksUp::holdingOpen(
        whatTheShedNamesAsNewest(['2.3.0'], [['disk.space', 1_790_000_000]]),
        whatTheShedNamesAsNewest(['2.5.0', '2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600], ['disk.space', 1_790_000_000]]),
    ));
    $listening->wakesAt(0)->wakesAt(2);

    expect(theMarksOnTheBar($listening->screen))->toBe([
        'health' => ['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.health')])],
        'repairs' => ['badge' => '', 'said' => ''],
        'services' => ['badge' => '', 'said' => ''],
        'updates' => ['badge' => '2', 'said' => trans_choice('news.new_on_tab', 2, ['tab' => __('navigation.updates')])],
    ])
        ->and(trans_choice('news.new_on_tab', 2, ['tab' => __('navigation.updates')]))->toBe('Updates, 2 new')
        ->and(trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.health', locale: 'nl')], 'nl'))->toBe('Gezondheid, 1 nieuw');
});

it('marks the tabs on each tab\'s screen, which holds the stack\'s stream while it is in front', function (TheTabs $tab): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $screen = theShedsTabHearing($tab, AStackThatSpeaksUp::holdingOpen(
        whatTheShedNamesAsNewest(['2.3.0'], [['disk.space', 1_790_000_000]]),
        whatTheShedNamesAsNewest(['2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600], ['disk.space', 1_790_000_000]]),
    ), $clock);

    $screen->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $screen->listen();

    expect(theMarksOnTheBar($screen))->toBe([
        'health' => ['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.health')])],
        'repairs' => ['badge' => '', 'said' => ''],
        'services' => ['badge' => '', 'said' => ''],
        'updates' => ['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.updates')])],
    ]);
})->with(TheTabs::cases());

it('marks the tabs on a screen no tab owns, which holds the stack\'s stream while it is in front', function (string $which): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $screen = theShedsOtherScreenHearing($which, AStackThatSpeaksUp::holdingOpen(
        whatTheShedNamesAsNewest(['2.3.0'], [['disk.space', 1_790_000_000]]),
        whatTheShedNamesAsNewest(['2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600], ['disk.space', 1_790_000_000]]),
    ), $clock);

    $screen->listen();
    $clock->moveTo(AScreenListening::secondsAfterOpening(2));
    $screen->listen();

    expect(theMarksOnTheBar($screen))->toBe([
        'health' => ['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.health')])],
        'repairs' => ['badge' => '', 'said' => ''],
        'services' => ['badge' => '', 'said' => ''],
        'updates' => ['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.updates')])],
    ]);
})->with(['what to do with a service', 'what a service said', 'the words the menu opens', 'following a walk']);

it('lets go of the stack\'s stream and of what else it hears when it stops', function (): void {
    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $stream = AStackThatSpeaksUp::holdingOpen();
    $walk = AStackThatNarrates::holdingOpen();
    $start = AStackThatSaysWhatItWaitsOn::saying();
    $following = theShedsOtherScreenHearing('following a walk', $stream, $clock, walk: $walk);
    $doing = theShedsOtherScreenHearing('what to do with a service', $stream, $clock, start: $start);

    $following->stop();
    $doing->stop();

    expect($stream->lettingsGo())->toBe(2)
        ->and($walk->lettingsGo())->toBe(1)
        ->and($start->lettingsGo())->toBe(1);
});

it('keeps the marks it has where a wake names nothing new', function (): void {
    $listening = theShedsHealthHearing(AStackThatSpeaksUp::holdingOpen(
        whatTheShedNamesAsNewest(['2.3.0'], []),
        whatTheShedNamesAsNewest(['2.4.0', '2.3.0'], []),
        WhatWasHeard::aSignOfLife(),
    ));
    $listening->wakesAt(0)->wakesAt(2)->wakesAt(4);

    expect(theMarksOnTheBar($listening->screen)['updates'])->toBe(['badge' => '1', 'said' => trans_choice('news.new_on_tab', 1, ['tab' => __('navigation.updates')])]);
});

it('carries no mark for a kind switched off', function (): void {
    $seal = ASealInMemory::working();
    $kept = NewsKeptInMemory::empty();
    $listening = theShedsHealthHearing(AStackThatSpeaksUp::holdingOpen(
        whatTheShedNamesAsNewest(['2.3.0'], [['disk.space', 1_790_000_000]]),
        whatTheShedNamesAsNewest(['2.4.0', '2.3.0'], [['vpn.leak', 1_790_000_600]]),
    ), $seal, $kept);
    $listening->wakesAt(0);
    NoticingWhatIsNew::markingOver($seal, $kept)->markNoLonger(theStackWhoseTabsAreMarked()->id(), KindOfNews::Update);
    $listening->wakesAt(2);

    expect(theMarksOnTheBar($listening->screen)['updates'])->toBe(['badge' => '', 'said' => ''])
        ->and(theMarksOnTheBar($listening->screen)['health']['badge'])->toBe('1');
});

it('says the mark as the tab\'s name on each platform, and the count drawn in the badge nowhere else', function (string $renderer, string ...$says): void {
    // The renderers are the package's, rewritten by `scripts/patch_nativephp.php`;
    // a mark the bar carries and no renderer says is a badge a screen reader
    // reads as a bare number, or not at all.
    foreach ($says as $line) {
        expect((string) file_get_contents(base_path($renderer)))->toContain($line);
    }
})->with([
    'Android' => [
        'vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeRootTabsRenderer.kt',
        'contentDescription = tab.props.getString("badge_label", "").ifEmpty { label }',
        'Text(badge, fontFamily = chromeFontFamily, modifier = Modifier.clearAndSetSemantics {})',
    ],
    'iOS' => [
        'vendor/nativephp/mobile/resources/xcode/NativePHP/NativeRender/NativeRootTabsRenderer.swift',
        '.accessibilityLabel(markFor(tab), isEnabled: !markFor(tab).isEmpty)',
        'tab.props.getString("badge_label", default: "")',
    ],
]);
