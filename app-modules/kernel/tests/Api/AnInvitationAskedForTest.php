<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\WhatBecomesOfUnrated;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhatTheInvitationAsked
{
    public function __construct(public string $said) {}
}

/** The age an invitation is asked with, and what becomes of unrated material, as one line. */
function whatItIsAskedWith(AnInvitationAskedFor $asked): string
{
    return sprintf(
        '%s|%s',
        $asked->age(
            upTo: static fn(int $age): WhatTheInvitationAsked => new WhatTheInvitationAsked(sprintf('%d', $age)),
            none: static fn(): WhatTheInvitationAsked => new WhatTheInvitationAsked('no limit'),
        )->said,
        $asked->unrated(
            chosen: static fn(WhatBecomesOfUnrated $unrated): WhatTheInvitationAsked => new WhatTheInvitationAsked($unrated->asked()),
            unsaid: static fn(): WhatTheInvitationAsked => new WhatTheInvitationAsked('left to the stack'),
        )->said,
    );
}

it('asks for a person and libraries, with no age limit and unrated material left to the stack', function (): void {
    $libraries = TheLibraries::of('Films');
    $asked = AnInvitationAskedFor::for('anna', $libraries);

    expect([$asked->name(), $asked->libraries(), whatItIsAskedWith($asked)])->toBe(['anna', $libraries, 'no limit|left to the stack']);
});

it('asks with an age limit, from none upwards, and keeps it through saying what becomes of unrated material', function (): void {
    $libraries = TheLibraries::of();
    $held = AnInvitationAskedFor::heldToAge(12, 'anna', $libraries)->withUnrated(WhatBecomesOfUnrated::HeldBack);

    expect([$held->name(), $held->libraries(), whatItIsAskedWith($held)])->toBe(['anna', $libraries, '12|block'])
        ->and(whatItIsAskedWith(AnInvitationAskedFor::heldToAge(0, 'anna', $libraries)))->toBe('0|left to the stack')
        ->and(whatItIsAskedWith(AnInvitationAskedFor::for('anna', $libraries)->withUnrated(WhatBecomesOfUnrated::LetThrough)))->toBe('no limit|allow');
});

it('refuses a blank name and an age below none', function (): void {
    expect(fn(): AnInvitationAskedFor => AnInvitationAskedFor::for(' ', TheLibraries::of()))->toThrow(InvitationSaysNothing::class, 'its `name` blank')
        ->and(fn(): AnInvitationAskedFor => AnInvitationAskedFor::heldToAge(12, '', TheLibraries::of()))->toThrow(InvitationSaysNothing::class, 'its `name` blank')
        ->and(fn(): AnInvitationAskedFor => AnInvitationAskedFor::heldToAge(-1, 'anna', TheLibraries::of()))->toThrow(InvitationSaysNothing::class, 'its `age_limit` at -1');
});

it('names somebody in the household, and refuses nobody', function (): void {
    expect(SomebodyInTheHousehold::called('anna')->name())->toBe('anna')
        ->and(fn(): SomebodyInTheHousehold => SomebodyInTheHousehold::called(' '))->toThrow(InvitationSaysNothing::class, 'its `name` blank');
});

it('asks for each act by the stack\'s own word for it', function (): void {
    expect(AskingThemIn::Invite->asked())->toBe('invite')
        ->and(AskingThemIn::TakeThePasswordOff->asked())->toBe('reissue')
        ->and(WhatBecomesOfUnrated::HeldBack->asked())->toBe('block')
        ->and(WhatBecomesOfUnrated::LetThrough->asked())->toBe('allow');
});
