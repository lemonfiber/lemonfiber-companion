<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;

use function sprintf;

// The resolver, tested as EDGE will call it: a bare token name in, a hex or
// null out.

it('answers for every token this surface asserts', function (): void {
    $resolve = Theme::resolver();
    $unanswered = [];

    foreach (ThemeToken::cases() as $token) {
        if ($resolve($token->value) !== $token->hex()) {
            $unanswered[] = $token->value;
        }
    }

    expect($unanswered)->toBe([], sprintf(
        "The resolver does not answer for these:\n  %s\n\n"
        . 'A token EDGE cannot resolve is dropped at render with no error anywhere, so a '
        . 'resolver that silently stops answering for one is a screen that quietly loses '
        . 'its accent.',
        implode("\n  ", $unanswered),
    ));
});

it('DES-R24 — answers with nothing for what the platform should own', function (): void {
    $resolve = Theme::resolver();

    // The names a template is most likely to reach for, and each of them is
    // something the platform's own theme roles decide. Null is the answer that
    // makes `tests/Templates` report the class, which is how a template asking
    // for one finds out.
    expect($resolve('background'))->toBeNull();
    expect($resolve('surface'))->toBeNull();
    expect($resolve('on-surface'))->toBeNull();
    expect($resolve('primary'))->toBeNull();
    expect($resolve(''))->toBeNull();
});
