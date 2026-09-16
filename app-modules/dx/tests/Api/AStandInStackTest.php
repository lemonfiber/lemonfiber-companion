<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Api;

use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Dx\Api\AStandInStack;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;

use function str_repeat;

// The three machines, and the properties the rest of the module trusts.
//
// None of these shows up as a failing screen if it breaks. It shows up as a
// stand-in behaving like the wrong machine — which reads as the app being
// wrong, on the one build somebody is looking at precisely to find out whether
// the app is wrong.

it('N1-R11 — no two stand-ins share an identity', function (): void {
    // Identity is what `N1-R11` keeps stacks apart by, and two sharing one
    // would be one machine wearing two names: the store folds them together on
    // `remember()`, so whichever was seeded last would be the only one listed.
    $seen = [];

    foreach (AStandInStack::cases() as $case) {
        $seen[] = $case->asAStack()->id()->stored();
    }

    expect($seen)->toHaveCount(count(array_unique($seen)));
});

it('is found by the identity it was built with', function (): void {
    foreach (AStandInStack::cases() as $case) {
        expect(AStandInStack::howAStackOfThisIdentityBehaves($case->asAStack()->id()))->toBe($case);
    }
});

it('treats a machine it has never heard of as a working one', function (): void {
    // The conservative direction, and it matters: a device holding a real
    // pairing beside the stand-ins is a state somebody can get into, and the
    // reading of *I have never heard of this machine* must not be *pretend it
    // is broken*.
    $elsewhere = StackId::of(Nonce::of(str_repeat('z', Nonce::SHORTEST)));

    expect(AStandInStack::howAStackOfThisIdentityBehaves($elsewhere))->toBe(AStandInStack::Answering);
});

it('N1-R10 — the three answer differently, which is the whole point', function (): void {
    // A build where every machine answers well is a build where the screens an
    // operator meets on a bad evening are unreachable.
    // `WhatARefusalMeant` reads `401` as a session to sign in again for and
    // everything else as a stack that is not answering, so these cover both of
    // its arms and the path where nothing went wrong.
    $answers = [];

    foreach (AStandInStack::cases() as $case) {
        $answers[] = $case->answersWith();
    }

    expect($answers)->toHaveCount(count(array_unique($answers)))
        ->and(AStandInStack::Answering->answersWith())->toBe(200)
        ->and(AStandInStack::RefusingTheSession->answersWith())->toBe(401);
});

it('N1-R60 — no address it carries can resolve anywhere', function (): void {
    // RFC 2606 reserves `.invalid`, so were the stand-in client ever to miss a
    // request the failure would be a name that does not exist rather than a
    // connection to somebody's actual machine.
    foreach (AStandInStack::cases() as $case) {
        expect($case->asAStack()->at()->forTheClient())->toEndWith('.invalid:8443');
    }
});

it('is named so nobody mistakes it for a machine of theirs', function (): void {
    foreach (AStandInStack::cases() as $case) {
        expect($case->called())->toStartWith('Stand-in')
            ->and($case->asAStack()->name()->shown())->toBe($case->called());
    }
});

it('finds machines to check', function (): void {
    // The floor. Every rule above walks the cases, so an empty enum would make
    // all of them pass with no iterations — which is the shape of silence every
    // rule in this repository is written against.
    expect(AStandInStack::cases())->not->toBeEmpty();
});
