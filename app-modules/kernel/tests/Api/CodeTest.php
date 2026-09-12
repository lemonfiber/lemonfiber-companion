<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\CodeIsBlank;

it('carries what the server sent', function (): void {
    expect(Code::of('STACK-7')->shown())->toBe('STACK-7');
});

it('is shown without the whitespace around it', function (): void {
    // A code arrives in JSON and is rendered beside a refusal. Padding is
    // invisible in the payload and visible on the screen as a misaligned row.
    expect(Code::of("  STACK-7\n")->shown())->toBe('STACK-7');
});

it('refuses a code that is empty', function (): void {
    expect(fn(): Code => Code::of(''))->toThrow(CodeIsBlank::class);
});

it('refuses a code that is only whitespace', function (): void {
    // The case a length check alone would let through, which is the one that
    // renders as a blank space where an identifier belongs.
    expect(fn(): Code => Code::of("  \t\n"))->toThrow(CodeIsBlank::class);
});

it('says what was wrong when it refuses', function (): void {
    expect(fn(): Code => Code::of(''))->toThrow(CodeIsBlank::class, 'A problem arrived with no code');
});

it('is the same code when the text is the same', function (): void {
    expect(Code::of('STACK-7')->is(Code::of('STACK-7')))->toBeTrue();
});

it('is a different code when the text differs', function (): void {
    expect(Code::of('STACK-7')->is(Code::of('STACK-8')))->toBeFalse();
});
