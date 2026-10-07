<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AProblemListed;
use Modules\Kernel\Api\AReleaseListed;
use Modules\Kernel\Api\ARequestListed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\News\Api\KindOfNews;
use Modules\Operator\Internal\Presenters\HowWhatIsNewReads;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Tests\Support\APhoneLookingAtWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// What's new, from the menu of any stack: everything newer than the newest of its
// kind the operator has seen on each stack, a section per kind, the stacks it
// could not reach first, and Mark all seen clearing what is shown.

/** A stack the phone holds, named. Named for this file. */
function aStackWithNews(string $seed, string $name): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($name),
        Address::of('https://192.168.1.47'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack lists, newest first in each kind.
 *
 * @param list<string>             $releases versions
 * @param array<int, string>       $requests number => title
 * @param array<string, int>       $problems check => onset
 */
function whatItLists(array $releases = [], array $requests = [], array $problems = []): TheNewsOfAStack
{
    $listedReleases = array_map(static fn(string $version): AReleaseListed => AReleaseListed::versioned($version, WhatAReleaseDelivers::saidNothing()), $releases);
    $listedRequests = [];
    foreach ($requests as $number => $title) {
        $listedRequests[] = new ARequestListed(RequestId::numbered($number), $title, 'Anna');
    }
    $listedProblems = [];
    foreach ($problems as $check => $onset) {
        $listedProblems[] = new AProblemListed(Check::of($check), Instant::atEpochSeconds($onset), sprintf('%s is wrong', $check));
    }

    return new TheNewsOfAStack(
        WhatTheStackListed::these(...$listedReleases),
        WhatTheStackListed::these(...$listedRequests),
        WhatTheStackListed::these(...$listedProblems),
    );
}

it('notes what a stack holds as seen the first time, and says nothing is new', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists(['0.18.0'], [12 => 'Dune'], ['service.sonarr' => 1000]));

    $drawn = WhatTheDeviceWouldDraw::by($phone->opens());

    expect($drawn->said())->toContain(__('news.nothing_new'), __('news.nothing_new_explained'))
        ->and($drawn->offers())->not->toContain(__('news.mark_all_seen'));
});

it('lists what came after, a section per kind in the order updates, requests, problems', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists(['0.17.0'], [11 => 'Bluey'], ['vpn.egress' => 900]));
    WhatTheDeviceWouldDraw::by($phone->opens());

    $phone->listing->lists($home->id(), whatItLists(['0.18.0', '0.17.0'], [12 => 'Dune', 11 => 'Bluey'], ['service.sonarr' => 1000, 'vpn.egress' => 900]));
    $said = WhatTheDeviceWouldDraw::by($phone->opens())->said();

    // What follows Mark all seen, past the chips that name the kinds too.
    $listed = array_slice($said, (int) array_search(__('news.mark_all_seen'), $said, strict: true));
    $order = array_values(array_filter($listed, static fn(string $line): bool => in_array($line, [
        __('news.kind.update'), 'lemonfiber 0.18.0', __('news.kind.request'), 'Dune', __('news.kind.problem'), 'service.sonarr is wrong',
    ], strict: true)));

    expect($order)->toBe([__('news.kind.update'), 'lemonfiber 0.18.0', __('news.kind.request'), 'Dune', __('news.kind.problem'), 'service.sonarr is wrong'])
        ->and($said)->toContain('Request · The loft')
        ->and($said)->not->toContain('Bluey', 'lemonfiber 0.17.0', 'vpn.egress is wrong');
});

it('shows one kind when asked, and Mark all seen clears only what is shown', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists([], [11 => 'Bluey'], ['vpn.egress' => 900]));
    WhatTheDeviceWouldDraw::by($phone->opens());
    $phone->listing->lists($home->id(), whatItLists([], [12 => 'Dune', 11 => 'Bluey'], ['service.sonarr' => 1000, 'vpn.egress' => 900]));

    $screen = $phone->opens();
    $screen->showKind(KindOfNews::Request->value);
    $narrowed = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->markAllSeen();
    $screen->showKind('');
    $after = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($narrowed)->toContain('Dune');
    expect($narrowed)->not->toContain('service.sonarr is wrong');
    expect($after)->toContain('service.sonarr is wrong');
    expect($after)->not->toContain('Dune');
});

it('offers the stacks to narrow by only where more than one is paired', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $away = aStackWithNews('b', 'The cabin');

    $one = WhatTheDeviceWouldDraw::by(new APhoneLookingAtWhatIsNew($home)->opens())->offers();
    $two = WhatTheDeviceWouldDraw::by(new APhoneLookingAtWhatIsNew($home, $away)->opens())->offers();

    expect($one)->not->toContain(__('news.all_stacks'))
        ->and($two)->toContain(__('news.all_stacks'), 'The loft', 'The cabin');
});

it('shows one stack when asked, and every stack again for a stack it does not hold', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $away = aStackWithNews('b', 'The cabin');
    $phone = new APhoneLookingAtWhatIsNew($home, $away);
    $phone->listing->lists($home->id(), whatItLists([], [11 => 'Bluey']));
    $phone->listing->lists($away->id(), whatItLists([], [5 => 'Up']));
    $first = $phone->opens();
    WhatTheDeviceWouldDraw::by($first);
    WhatTheDeviceWouldDraw::by($first);
    $phone->listing->lists($home->id(), whatItLists([], [12 => 'Dune', 11 => 'Bluey']));
    $phone->listing->lists($away->id(), whatItLists([], [6 => 'Heat', 5 => 'Up']));

    $screen = $phone->opens();
    $screen->showStack($away->id()->stored());
    $narrowed = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->showStack('a stack this phone does not hold');
    WhatTheDeviceWouldDraw::by($screen);
    $every = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($narrowed)->toContain('Heat')
        ->and($narrowed)->not->toContain('Dune')
        ->and($every)->toContain('Heat', 'Dune');
});

it('opens on the stack a stack\'s menu handed it, and on every stack for one it does not hold', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $away = aStackWithNews('b', 'The cabin');
    $phone = new APhoneLookingAtWhatIsNew($home, $away);

    $handed = $phone->opens();
    $handed->setData([AScreenWithoutAStack::WHATS_NEW_SHOWS => $away->id()->stored()]);
    $handed->mount();
    $forgotten = $phone->opens();
    $forgotten->setData([AScreenWithoutAStack::WHATS_NEW_SHOWS => 'a stack this phone does not hold']);
    $forgotten->mount();
    $nothing = $phone->opens();
    $nothing->setData([AScreenWithoutAStack::WHATS_NEW_SHOWS => 7]);
    $nothing->mount();

    expect($handed->stackShown)->toBe($away->id()->stored())
        ->and($forgotten->stackShown)->toBe(HowWhatIsNewReads::EVERYTHING)
        ->and($nothing->stackShown)->toBe(HowWhatIsNewReads::EVERYTHING);
});

it('says a request it has no title for as a request', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists([], [11 => 'Bluey']));
    WhatTheDeviceWouldDraw::by($phone->opens());
    $phone->listing->lists($home->id(), whatItLists([], [12 => '', 11 => 'Bluey']));

    $said = WhatTheDeviceWouldDraw::by($phone->opens())->said();

    expect($said)->toContain(__('news.kind_one.request'), 'Request · The loft');
});

it('opens nothing for a kind, a stack or an item it was not showing', function (string $stack, string $kind, string $named): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists(['0.17.0']));
    WhatTheDeviceWouldDraw::by($phone->opens());
    $phone->listing->lists($home->id(), whatItLists(['0.18.0', '0.17.0']));
    $screen = $phone->opens();
    WhatTheDeviceWouldDraw::by($screen);

    $screen->open($stack === '' ? $home->id()->stored() : $stack, $kind, $named);

    expect($screen->getNavigationIntent())->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($phone->opens())->said())->toContain('lemonfiber 0.18.0');
})->with([
    'a kind there is not' => ['', 'rumour', '0.18.0'],
    'a stack the phone does not hold' => ['a stack this phone does not hold', 'update', '0.18.0'],
    'an item the stack did not list' => ['', 'update', '0.19.0'],
]);

it('puts a stack it could not reach first, with when the phone last heard from it', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->cannotBeReached($home->id(), Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $phone->standings->lastHeard($home->id(), HowItStands::Healthy, Instant::atEpochSeconds(APhoneLookingAtWhatIsNew::NOW - 600));

    $said = WhatTheDeviceWouldDraw::by($phone->opens())->said();

    expect($said)->toContain(__('news.unreached', ['stack' => 'The loft']))
        ->and($said)->toContain(__('news.unreached_since', ['ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->not->toContain(__('news.nothing_new'));
});

it('opens an update on Updates, a request on Requests and a problem on Health opened out, and marks each seen', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists(['0.17.0'], [11 => 'Bluey'], ['vpn.egress' => 900]));
    WhatTheDeviceWouldDraw::by($phone->opens());
    $phone->listing->lists($home->id(), whatItLists(['0.18.0', '0.17.0'], [12 => 'Dune', 11 => 'Bluey'], ['service.sonarr' => 1000]));
    $screen = $phone->opens();
    WhatTheDeviceWouldDraw::by($screen);

    $screen->open($home->id()->stored(), KindOfNews::Problem->value, 'service.sonarr');
    $toHealth = [$screen->getNavigationIntent()?->uri, $screen->getNavigationIntent()?->data];
    $screen->open($home->id()->stored(), KindOfNews::Update->value, '0.18.0');
    $toUpdates = [$screen->getNavigationIntent()?->uri, $screen->getNavigationIntent()?->data];
    $screen->open($home->id()->stored(), KindOfNews::Request->value, '12');
    $toRequests = [$screen->getNavigationIntent()?->uri, $screen->getNavigationIntent()?->data];

    expect($toHealth)->toBe([AStacksScreen::Health->forTheStack($home->id()), []])
        ->and($toUpdates)->toBe([AStacksScreen::Updates->forTheStack($home->id()), []])
        ->and($toRequests)->toBe([AStacksScreen::Requests->forTheStack($home->id()), []])
        ->and(WhatTheDeviceWouldDraw::by($phone->opens())->said())->toContain(__('news.nothing_new'));
});

it('takes no list from a kind the stack could not read, and marks nothing of it once it can', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), new TheNewsOfAStack(WhatTheStackListed::these(), WhatTheStackListed::unread(), WhatTheStackListed::these()));
    WhatTheDeviceWouldDraw::by($phone->opens());

    $phone->listing->lists($home->id(), whatItLists([], [12 => 'Dune']));

    $said = WhatTheDeviceWouldDraw::by($phone->opens())->said();

    expect($said)->toContain(__('news.nothing_new'));
    expect($said)->not->toContain('Dune');
});

it('reads each stack once while open, and again when it looks again', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $screen = $phone->opens();

    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $once = $phone->listing->askings($home->id());
    $screen->whileOpen();
    WhatTheDeviceWouldDraw::by($screen);

    expect($once)->toBe(1)->and($phone->listing->askings($home->id()))->toBe(2);
});

it('asks every stack shown again what it offers when the operator asks again, and not on the screen\'s own cadence', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $away = aStackWithNews('b', 'The cabin');
    $phone = new APhoneLookingAtWhatIsNew($home, $away);
    $screen = $phone->opens();

    $screen->again();
    $onTheCadence = [$phone->offering->wasAskedAgain($home->id()), $phone->offering->wasAskedAgain($away->id())];
    $screen->askAgain();

    expect($onTheCadence)->toBe([false, false])
        ->and([$phone->offering->wasAskedAgain($home->id()), $phone->offering->wasAskedAgain($away->id())])->toBe([true, true]);
});

it('reads one stack a frame, and says nothing is new only once every stack shown was read', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $away = aStackWithNews('b', 'The cabin');
    $phone = new APhoneLookingAtWhatIsNew($home, $away);
    $screen = $phone->opens();

    $first = WhatTheDeviceWouldDraw::by($screen)->said();
    $afterTheFirst = [$phone->listing->askings($home->id()), $phone->listing->askings($away->id()), $screen->waitsForTheNextFrame()];
    $second = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($afterTheFirst)->toBe([1, 0, true])
        ->and($first)->not->toContain(__('news.nothing_new'))
        ->and([$phone->listing->askings($home->id()), $phone->listing->askings($away->id()), $screen->waitsForTheNextFrame()])->toBe([1, 1, false])
        ->and($second)->toContain(__('news.nothing_new'));
});

it('marks nothing of a kind the stack no longer marks as new', function (): void {
    $home = aStackWithNews('a', 'The loft');
    $phone = new APhoneLookingAtWhatIsNew($home);
    $phone->listing->lists($home->id(), whatItLists([], [11 => 'Bluey']));
    WhatTheDeviceWouldDraw::by($phone->opens());
    $phone->marking->markNoLonger($home->id(), KindOfNews::Request);
    $phone->listing->lists($home->id(), whatItLists([], [12 => 'Dune', 11 => 'Bluey']));

    expect(WhatTheDeviceWouldDraw::by($phone->opens())->said())->not->toContain('Dune');
});
