<?php

declare(strict_types=1);

use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Tests\Support\APhoneHoldingTwoStacks;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\StacksInMemory;

// Where the phone opens once a stack is taken off it: the next stack in the
// operator's order, the first where it was the last, and the list where it was
// the only one.

it('opens the stack after the one removed, in the operator\'s order', function (): void {
    [$a, $b, $c] = [APhoneHoldingTwoStacks::aStack('a'), APhoneHoldingTwoStacks::aStack('b'), APhoneHoldingTwoStacks::aStack('c')];
    $around = AroundThePhone::holding(StacksInMemory::holding($a, $b, $c));

    expect($around->afterRemoving($a))->toBe($around->choosingLeadsTo($b))
        ->and($around->afterRemoving($b))->toBe($around->choosingLeadsTo($c));
});

it('opens the first stack where the one removed was the last', function (): void {
    [$a, $b, $c] = [APhoneHoldingTwoStacks::aStack('a'), APhoneHoldingTwoStacks::aStack('b'), APhoneHoldingTwoStacks::aStack('c')];
    $around = AroundThePhone::holding(StacksInMemory::holding($a, $b, $c));

    expect($around->afterRemoving($c))->toBe($around->choosingLeadsTo($a));
});

it('opens the list where the one removed was the only one', function (): void {
    $only = APhoneHoldingTwoStacks::aStack('a');

    expect(AroundThePhone::holding(StacksInMemory::holding($only))->afterRemoving($only))->toBe(AScreenWithoutAStack::TheList->value);
});
