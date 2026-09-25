<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;

/** One line carried out of an arm. */
final readonly class WhatTheInvitationGranted
{
    public function __construct(public string $said) {}
}

/** An invitation for Anna at the stack's address. */
function anInvitationForAnna(): AnInvitationToHand
{
    return AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', 'The number can change'), 72);
}

/** What an invitation granted, folded to one line. */
function whatItGranted(AnInvitation $invitation): string
{
    return $invitation->granted(
        these: static fn(WhatWasGranted $granted): WhatTheInvitationGranted => new WhatTheInvitationGranted($granted->filtering()),
        nothing: static fn(): WhatTheInvitationGranted => new WhatTheInvitationGranted('nothing'),
    )->said;
}

it('keeps everything a rehearsal was answered with, and says it was one', function (): void {
    $toHand = anInvitationForAnna();
    $withdrawn = WhoWasTakenBack::of('bob');
    $invitation = AnInvitation::rehearsed($toHand, WhereTheInvitationStands::Made, WhetherTheyCanAsk::NotTried, $withdrawn);

    expect([$invitation->toHand(), $invitation->standing(), $invitation->linked(), $invitation->withdrawn(), $invitation->wasRehearsed()])
        ->toBe([$toHand, WhereTheInvitationStands::Made, WhetherTheyCanAsk::NotTried, $withdrawn, true]);
});

it('keeps everything an invitation carried out was answered with, and says it was not rehearsed', function (): void {
    $toHand = anInvitationForAnna();
    $withdrawn = WhoWasTakenBack::of();
    $invitation = AnInvitation::carriedOut($toHand, WhereTheInvitationStands::Waiting, WhetherTheyCanAsk::NotYet, $withdrawn);

    expect([$invitation->toHand(), $invitation->standing(), $invitation->linked(), $invitation->withdrawn(), $invitation->wasRehearsed()])
        ->toBe([$toHand, WhereTheInvitationStands::Waiting, WhetherTheyCanAsk::NotYet, $withdrawn, false]);
});

it('says it granted nothing until it is given what it granted, and keeps the rest when it is', function (): void {
    $toHand = anInvitationForAnna();
    $withdrawn = WhoWasTakenBack::of('bob');
    $bare = AnInvitation::rehearsed($toHand, WhereTheInvitationStands::Reset, WhetherTheyCanAsk::Made, $withdrawn);
    $granting = $bare->granting(WhatWasGranted::granted(TheLibraries::of(), WhatBecomesOfUnrated::HeldBack, WhetherTheyCanAsk::Made, 'A limit is not a lock', ''));

    expect(whatItGranted($bare))->toBe('nothing')
        ->and(whatItGranted($granting))->toBe('A limit is not a lock')
        ->and([$granting->toHand(), $granting->standing(), $granting->linked(), $granting->withdrawn(), $granting->wasRehearsed()])
        ->toBe([$toHand, WhereTheInvitationStands::Reset, WhetherTheyCanAsk::Made, $withdrawn, true]);
});

it('keeps the name, the address and the hours it was handed', function (): void {
    $address = AnAddressToHand::at('http://loft.local:8096', '');
    $toHand = AnInvitationToHand::to('anna', $address, 0);

    expect([$toHand->name(), $toHand->address(), $toHand->hours()])->toBe(['anna', $address, 0]);
});

it('refuses an invitation with no name, no address, or fewer hours than none', function (): void {
    $address = AnAddressToHand::at('http://loft.local:8096', '');

    expect(fn(): AnInvitationToHand => AnInvitationToHand::to(' ', $address, 72))->toThrow(InvitationSaysNothing::class, 'its `name` blank')
        ->and(fn(): AnInvitationToHand => AnInvitationToHand::to('anna', AnAddressToHand::none(), 72))->toThrow(InvitationSaysNothing::class, 'its `address` blank')
        ->and(fn(): AnInvitationToHand => AnInvitationToHand::to('anna', $address, -1))->toThrow(InvitationSaysNothing::class, 'its `hours` at -1, which is fewer than none');
});

it('keeps what was granted, with a limit or with none', function (): void {
    $libraries = TheLibraries::of('Films', 'Kids');
    $granted = WhatWasGranted::granted($libraries, WhatBecomesOfUnrated::LetThrough, WhetherTheyCanAsk::NotYet, 'A limit is not a lock', 'PG-13');

    expect([$granted->libraries(), $granted->unrated(), $granted->requesting(), $granted->filtering(), $granted->limit()])
        ->toBe([$libraries, WhatBecomesOfUnrated::LetThrough, WhetherTheyCanAsk::NotYet, 'A limit is not a lock', 'PG-13'])
        ->and(WhatWasGranted::granted($libraries, WhatBecomesOfUnrated::HeldBack, WhetherTheyCanAsk::Made, 'A limit is not a lock', '')->limit())->toBe('');
});

it('refuses a grant with no word on what a limit is, or a limit that is blank rather than empty', function (): void {
    expect(fn(): WhatWasGranted => WhatWasGranted::granted(TheLibraries::of(), WhatBecomesOfUnrated::HeldBack, WhetherTheyCanAsk::Made, ' ', ''))
        ->toThrow(InvitationSaysNothing::class, 'its `filtering` blank')
        ->and(fn(): WhatWasGranted => WhatWasGranted::granted(TheLibraries::of(), WhatBecomesOfUnrated::HeldBack, WhetherTheyCanAsk::Made, 'A limit is not a lock', ' '))
        ->toThrow(InvitationSaysNothing::class, 'its `limit` blank');
});

it('holds libraries and names taken back in the order given, and refuses a blank one', function (): void {
    $libraries = TheLibraries::of(...['b' => 'Films', 'a' => 'Kids']);
    $withdrawn = WhoWasTakenBack::of(...['b' => 'bob', 'a' => 'carol']);

    expect(iterator_to_array($libraries, preserve_keys: true))->toBe(['Films', 'Kids'])
        ->and($libraries)->toHaveCount(2)
        ->and(iterator_to_array($withdrawn, preserve_keys: true))->toBe(['bob', 'carol'])
        ->and($withdrawn)->toHaveCount(2)
        ->and(fn(): TheLibraries => TheLibraries::of('Films', ' '))->toThrow(InvitationSaysNothing::class, 'its `libraries` blank')
        ->and(fn(): WhoWasTakenBack => WhoWasTakenBack::of(''))->toThrow(InvitationSaysNothing::class, 'its `withdrawn` blank');
});

it('leaves something to hand over for everybody but somebody who has joined', function (): void {
    expect(WhereTheInvitationStands::Made->leavesSomethingToHandOver())->toBeTrue()
        ->and(WhereTheInvitationStands::Waiting->leavesSomethingToHandOver())->toBeTrue()
        ->and(WhereTheInvitationStands::Reset->leavesSomethingToHandOver())->toBeTrue()
        ->and(WhereTheInvitationStands::Joined->leavesSomethingToHandOver())->toBeFalse();
});

it('says each word on the screen by a key of its own', function (): void {
    expect(WhereTheInvitationStands::Joined->saidOnTheScreen())->toBe('stacks.invitation.standing.joined')
        ->and(WhetherTheyCanAsk::NotYet->saidOnTheScreen())->toBe('stacks.invitation.asking.not-yet')
        ->and(WhatBecomesOfUnrated::HeldBack->saidOnTheScreen())->toBe('stacks.invitation.unrated.held-back');
});
