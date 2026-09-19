<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Whose;

it('N3-R1 — takes the arm the subject names', function (): void {
    expect(Whose::theOperator()->either(
        operator: static fn(): Code => Code::of('the operator'),
        member: static fn(string $id): Code => Code::of($id),
    )->shown())->toBe('the operator');

    expect(Whose::member('a7f3')->either(
        operator: static fn(): Code => Code::of('the operator'),
        member: static fn(string $id): Code => Code::of($id),
    )->shown())->toBe('a7f3');
});

it('N3-R1 — reads a blank identifier as the operator rather than as a member', function (): void {
    // A stack that answered with an empty member has said what one that left it out
    // said. Taking it at face value would sign somebody in against an account that
    // does not exist, and every read made on their behalf would be for nobody.
    foreach (['', ' ', "\t"] as $blank) {
        expect(Whose::member($blank)->is(Whose::theOperator()))->toBeTrue();
    }
});

it('N3-R1 — is the same person or it is not', function (): void {
    expect(Whose::member('a7f3')->is(Whose::member('a7f3')))->toBeTrue()
        ->and(Whose::member('a7f3')->is(Whose::member('b2e9')))->toBeFalse()
        ->and(Whose::theOperator()->is(Whose::theOperator()))->toBeTrue()
        ->and(Whose::theOperator()->is(Whose::member('a7f3')))->toBeFalse();
});

it('N3-R1 — goes into a store and comes back the same person', function (): void {
    // Total in both directions, which is what lets a store write this without an
    // accessor that hands back a member or nothing. Every subject is one string and
    // every string is one subject, so there is no value either side cannot name.
    foreach ([Whose::theOperator(), Whose::member('a7f3')] as $whose) {
        expect(Whose::member($whose->forTheStore())->is($whose))->toBeTrue();
    }

    // And the operator is the empty string, which is the statement the stack makes by
    // leaving the member off an admission, said in the shape a keychain holds.
    expect(Whose::theOperator()->forTheStore())->toBe('')
        ->and(Whose::member('a7f3')->forTheStore())->toBe('a7f3');
});

it('N3-R1 — hands the member arm the identifier it is about', function (): void {
    expect(Whose::member('a7f3')->either(
        operator: static fn(): Code => Code::of('nobody'),
        member: static fn(string $id): Code => Code::of($id),
    )->shown())->toBe('a7f3');
});
