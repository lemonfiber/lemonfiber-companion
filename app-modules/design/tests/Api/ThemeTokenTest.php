<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Design\Api\ThemeToken;

use function preg_match;
use function sprintf;

// Every case, driven off `cases()` rather than written out.
//
// A token added tomorrow is checked the moment it exists: it has to answer with
// a hex the parser will accept, and it has to have been thought about as text,
// which is the question that DES-R15 turns on and the one that is easiest to
// leave unanswered. Writing the cases out here would mean a new case is checked
// only if somebody remembers to add a line, and a forgotten check reads exactly
// like a passing one.

it('every token answers with a hex EDGE can paint', function (): void {
    $malformed = [];

    foreach (ThemeToken::cases() as $token) {
        if (preg_match('/^#[0-9A-F]{6}$/', $token->hex()) !== 1) {
            $malformed[] = sprintf('%s answers %s', $token->value, $token->hex());
        }
    }

    expect($malformed)->toBe([], sprintf(
        "These answer with something the parser will not paint:\n  %s\n\n"
        . 'The resolver hands its answer straight to EDGE, which puts it on the native '
        . 'node as a colour. Anything that is not a six-digit hex arrives at the render '
        . 'layer as a colour nobody can read, and the failure is a screen that looks '
        . 'wrong rather than anything that reports.',
        implode("\n  ", $malformed),
    ));
});

it('DES-R15 — the accent is never text and its foreground always is', function (): void {
    // The pair, stated: `lemon` measures 1.6:1 on `paper` and is a fill, and
    // `ink` is the one foreground the brand pairs with it. Asserted by name
    // rather than by looping, because this is the brand decision itself and a
    // loop would only re-derive it from the code under test.
    expect(ThemeToken::Accent->safeAsText())->toBeFalse();
    expect(ThemeToken::OnAccent->safeAsText())->toBeTrue();
});

it('a token nobody wrote resolves to nothing', function (): void {
    expect(ThemeToken::tryFrom('background'))->toBeNull();
    expect(ThemeToken::tryFrom('on-surface'))->toBeNull();
    expect(ThemeToken::tryFrom('primary'))->toBeNull();
});
