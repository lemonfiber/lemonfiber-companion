<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatWasFoundOfTheMembers;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhoWasFoundIn
{
    public function __construct(public string $said) {}
}

it('keeps a member by name, joined or still invited', function (): void {
    $anna = AMember::joined('anna');
    $bob = AMember::stillInvited('bob');

    expect([$anna->name(), $anna->hasJoined(), $bob->name(), $bob->hasJoined()])->toBe(['anna', true, 'bob', false])
        ->and(fn(): AMember => AMember::joined(' '))->toThrow(InvitationSaysNothing::class, 'its `name` blank')
        ->and(fn(): AMember => AMember::stillInvited(''))->toThrow(InvitationSaysNothing::class, 'its `name` blank');
});

it('holds members in the stack\'s order', function (): void {
    $anna = AMember::joined('anna');
    $bob = AMember::stillInvited('bob');
    $members = TheMembers::of(...['b' => $anna, 'a' => $bob]);

    expect(iterator_to_array($members, preserve_keys: true))->toBe([$anna, $bob])
        ->and($members)->toHaveCount(2)
        ->and(TheMembers::of())->toHaveCount(0);
});

it('takes the arm it was made on', function (): void {
    $members = TheMembers::of(AMember::joined('anna'));
    $found = WhatWasFoundOfTheMembers::found($members)->either(
        found: static fn(TheMembers $held): WhoWasFoundIn => new WhoWasFoundIn($held === $members ? 'found' : 'other'),
        met: static fn(Obstacle $why): WhoWasFoundIn => new WhoWasFoundIn($why->value),
    )->said;
    $met = WhatWasFoundOfTheMembers::met(Obstacle::StackDidNotAnswer)->either(
        found: static fn(TheMembers $held): WhoWasFoundIn => new WhoWasFoundIn(sprintf('%d found', count($held))),
        met: static fn(Obstacle $why): WhoWasFoundIn => new WhoWasFoundIn($why->value),
    )->said;

    expect([$found, $met])->toBe(['found', Obstacle::StackDidNotAnswer->value]);
});
