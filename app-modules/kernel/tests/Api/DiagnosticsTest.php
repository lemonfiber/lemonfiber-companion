<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Diagnostics;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WireVersion;

const A_LOFT = 'a1b2c3d4e5f60718';
const A_SHED = 'b2c3d4e5f6071829';

it('says what somebody helping actually needs', function (): void {
    $said = Diagnostics::assemble(
        Shape::current(),
        WireVersion::One,
        StackId::of(Nonce::of(A_LOFT)),
    )->text();

    expect($said)->toContain('state shape 1')
        ->and($said)->toContain('api version')
        ->and($said)->toContain('stacks configured: 1')
        ->and($said)->toContain(A_LOFT);
});

it('counts the stacks it was given, including none', function (): void {
    // The boundary that matters: a device with nothing paired is exactly the
    // device whose operator is most likely to be asking for help.
    $said = Diagnostics::assemble(Shape::current(), WireVersion::One)->text();

    expect($said)->toContain('stacks configured: 0');
});

it('names every stack it was given', function (): void {
    $said = Diagnostics::assemble(
        Shape::current(),
        WireVersion::One,
        StackId::of(Nonce::of(A_LOFT)),
        StackId::of(Nonce::of(A_SHED)),
    )->text();

    expect($said)->toContain('stacks configured: 2')
        ->and($said)->toContain(A_LOFT)
        ->and($said)->toContain(A_SHED);
});

it('N4-R13 — says in the report itself what it does not contain', function (): void {
    // For the person reading it rather than for a rule. Somebody asked to send
    // a diagnostic bundle is being asked to trust it, and a file that says what
    // it withheld is one they can check at a glance — which is worth more than
    // the same promise in documentation they will not read.
    expect(Diagnostics::assemble(Shape::current(), WireVersion::One)->text())
        ->toContain('no credential');
});

it('is named the same thing every time', function (): void {
    // A name carrying the moment it was made differs between two reports about
    // the same fault, which is how a support thread ends up with four
    // attachments and nobody sure which is current.
    $first = Diagnostics::assemble(Shape::current(), WireVersion::One)->named();
    $second = Diagnostics::assemble(Shape::current(), WireVersion::One, StackId::of(Nonce::of(A_LOFT)))->named();

    expect($first)->toBe($second)
        ->and($first)->toEndWith('.txt');
});
