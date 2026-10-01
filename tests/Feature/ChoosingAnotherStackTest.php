<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
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
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Operator\Internal\ViewModels\AStackToChooseAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ADoorThatWasKnockedOn;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\TheWaysAround;
use Tests\Support\WhatTheDeviceWouldDraw;

/** A moment the kept words were heard at: ten seconds before the moment the phone the tests make reads. */
const HEARD_AT = 1_789_999_990;

function aStackToChooseFrom(string $called, string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.80'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

function theAtticToChooseFrom(): Stack
{
    return aStackToChooseFrom('The attic', 'a');
}

function theBarnToChooseFrom(): Stack
{
    return aStackToChooseFrom('The barn', 'b');
}

/** A tab on the attic, on a phone holding the attic and the barn, with the list of stacks shut. */
function aTabToChooseFrom(?AKeychainInMemory $keychain = null, ?StandingsInMemory $standings = null): HowCurrentThisStackIs
{
    $attic = theAtticToChooseFrom();
    $sessions = $keychain ?? AKeychainInMemory::working();
    $screen = new HowCurrentThisStackIs(
        AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        $sessions,
        AroundThePhone::holding(StacksInMemory::holding($attic, theBarnToChooseFrom()), $standings, $sessions),
    );
    $screen->setParams(['stack' => $attic->id()->stored()]);

    return $screen;
}

/**
 * The type of every node in a frame, the frame's own first.
 *
 * @param array<mixed> $node
 *
 * @return list<mixed>
 */
function typesDrawnIn(array $node): array
{
    $types = [array_key_exists('type', $node) ? $node['type'] : ''];
    $children = array_key_exists('children', $node) && is_array($node['children']) ? $node['children'] : [];

    foreach ($children as $child) {
        $types = [...$types, ...(is_array($child) ? typesDrawnIn($child) : [])];
    }

    return $types;
}

/**
 * What a screen reader is told each row of the list is, in the order they are drawn.
 *
 * @param array<mixed> $node
 *
 * @return list<mixed>
 */
function rowsReadAloudIn(array $node): array
{
    $read = aRowReadAloud($node);
    $children = array_key_exists('children', $node) && is_array($node['children']) ? $node['children'] : [];

    foreach ($children as $child) {
        $read = [...$read, ...(is_array($child) ? rowsReadAloudIn($child) : [])];
    }

    return $read;
}

/**
 * What a screen reader is told a node is, where it is a row.
 *
 * @param array<mixed> $node
 *
 * @return list<mixed>
 */
function aRowReadAloud(array $node): array
{
    $props = array_key_exists('props', $node) && is_array($node['props']) ? $node['props'] : [];
    $isARow = array_key_exists('type', $node) && $node['type'] === 'list_item';

    return $isARow && array_key_exists('a11y_label', $props) ? [$props['a11y_label']] : [];
}

/** Where the screen was sent, and nowhere where it was sent nowhere. */
function whereItWasSent(HowCurrentThisStackIs $screen): string
{
    return $screen->getNavigationIntent()->uri ?? '';
}

it('draws the stack\'s name in the top bar as the control that opens the list, and nothing of the list while it is shut', function (): void {
    $drawn = WhatTheDeviceWouldDraw::inTheListOfStacks(aTabToChooseFrom());

    expect($drawn->said())->toBe(['The attic'])
        ->and($drawn->offers())->toBe(['The attic']);
});

it('lists every stack in the phone\'s order with how it last stood, marks the current one, ends with adding a stack, and says how each is reached', function (): void {
    $standings = StandingsInMemory::working()->lastHeard(theBarnToChooseFrom()->id(), HowItStands::Broken, Instant::atEpochSeconds(HEARD_AT));
    $screen = aTabToChooseFrom(standings: $standings);
    $screen->chooseAStack();

    $drawn = WhatTheDeviceWouldDraw::inTheListOfStacks($screen);

    expect(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe([
        'The attic',
        __('navigation.your_stacks'),
        'The attic',
        __(HowItStands::Unknown->saidInAWord()),
        'The barn',
        __(HowItStands::Broken->saidInAWord()),
        __('navigation.switcher.add'),
        __('connection.encrypted'),
    ])->and($drawn->offers())->toBe(['The attic', 'The attic', 'The barn', __('navigation.switcher.add')])
        ->and(array_map(static fn(AStackToChooseAsShown $stack): string => $stack->pressed(), $screen->stacksToChooseFrom()))
        ->toBe(['stopChoosingAStack()', sprintf("openTheStack('%s')", theBarnToChooseFrom()->id()->stored())]);
});

it('marks the current stack with a check and says so to a screen reader, rather than by the colour of how it stands', function (): void {
    $screen = aTabToChooseFrom();
    $screen->chooseAStack();

    expect(rowsReadAloudIn(['children' => TheWaysAround::theListOfStacksIn(WhatTheDeviceWouldDraw::tree($screen))]))->toBe([
        __('connection.current_stack', ['stack' => 'The attic']),
        __('connection.open_stack', ['stack' => 'The barn']),
        __('navigation.switcher.add'),
    ]);

    [$attic, $barn] = $screen->stacksToChooseFrom();

    expect($attic->current)->toBeTrue()
        ->and([$attic->icon(), $attic->iosIcon(), $attic->tone()])->toBe(['check_circle', 'checkmark.circle.fill', ''])
        ->and($barn->current)->toBeFalse()
        ->and([$barn->icon(), $barn->iosIcon()])->toBe(['', ''])
        ->and($barn->tone())->not->toBe('');
});

it('reads nothing of what the phone kept while the list is shut', function (): void {
    expect(aTabToChooseFrom()->stacksToChooseFrom())->toBe([]);
});

it('opens another stack where choosing it leads, and shuts the list as it does', function (?Whose $whose, string $leads): void {
    $barn = theBarnToChooseFrom();
    $keychain = AKeychainInMemory::working();

    if ($whose instanceof Whose) {
        $keychain->keep($barn->id(), Session::of('a-session-not-a-secret'), $whose);
    }

    $screen = aTabToChooseFrom($keychain);
    $screen->chooseAStack();
    $screen->openTheStack($barn->id()->stored());

    expect($screen->choosingAStack)->toBeFalse()
        ->and(whereItWasSent($screen))->toBe(sprintf($leads, $barn->id()->stored()))
        ->and(NativeRouter::resolve(whereItWasSent($screen)))->not->toBeNull();
})->with([
    'nobody signed in, to its sign-in' => [null, '/stacks/%s/sign-in'],
    'the operator signed in, to its health' => [Whose::theOperator(), '/stacks/%s'],
    'a member signed in, to what they are owed' => [Whose::member('the-member-the-server-files-them-under'), '/stacks/%s/yours'],
]);

it('only shuts the list when the current stack is chosen', function (): void {
    $screen = aTabToChooseFrom();
    $screen->chooseAStack();

    [$attic] = $screen->stacksToChooseFrom();

    expect($attic->pressed())->toBe('stopChoosingAStack()');

    $screen->stopChoosingAStack();

    expect($screen->choosingAStack)->toBeFalse()
        ->and($screen->getNavigationIntent())->toBeNull();
});

it('begins pairing another stack from the foot of the list, and shuts the list as it does', function (): void {
    $screen = aTabToChooseFrom();
    $screen->chooseAStack();
    $screen->addAStack();

    expect($screen->choosingAStack)->toBeFalse()
        ->and(whereItWasSent($screen))->toBe(AScreenWithoutAStack::PairByScanning->value);
});

it('gives signing in to a stack the list of stacks and the menu, and not the bar', function (): void {
    $attic = theAtticToChooseFrom();
    $screen = new SignIntoAStack(
        ADoorThatWasKnockedOn::opening(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(HEARD_AT)),
        AKeychainInMemory::working(),
        AroundThePhone::holding(StacksInMemory::holding($attic)),
    );
    $screen->setParams(['stack' => $attic->id()->stored()]);

    $types = typesDrawnIn(WhatTheDeviceWouldDraw::tree($screen));

    expect(WhatTheDeviceWouldDraw::inTheListOfStacks($screen)->offers())->toBe(['The attic'])
        ->and($types)->toContain('native_drawer')
        ->and($types)->not->toContain('bottom_nav_item');
});
