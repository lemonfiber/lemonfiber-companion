<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function expect;
use function hexdec;
use function implode;
use function it;
use function max;
use function min;

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;

use function preg_match;
use function round;
use function sprintf;
use function substr;

// Driven off `cases()` where the rule is general, so a role added later is
// checked the moment it exists; written out where the rule is the brand's
// decision itself.

/** One channel of a hex colour, linearised as WCAG 2 defines it. */
function linearChannel(string $hex, int $offset): float
{
    $channel = hexdec(substr($hex, $offset, 2)) / 255;

    return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
}

/** The WCAG 2 relative luminance of a `#RRGGBB` colour. */
function luminance(string $hex): float
{
    return 0.2126 * linearChannel($hex, 1) + 0.7152 * linearChannel($hex, 3) + 0.0722 * linearChannel($hex, 5);
}

/** The WCAG 2 contrast ratio between two `#RRGGBB` colours. */
function contrast(string $one, string $other): float
{
    $lighter = max(luminance($one), luminance($other));
    $darker = min(luminance($one), luminance($other));

    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Every pairing this surface sets text in, as foreground => grounds.
 *
 * A tonal button is text on a line-coloured fill, and a label on an accent
 * fill is on-accent on accent.
 *
 * @return list<array{ThemeToken, ThemeToken}>
 */
function pairingsSetAsText(): array
{
    return [
        [ThemeToken::Text, ThemeToken::Surface],
        [ThemeToken::Text, ThemeToken::Raised],
        [ThemeToken::Muted, ThemeToken::Surface],
        [ThemeToken::Muted, ThemeToken::Raised],
        [ThemeToken::Faint, ThemeToken::Surface],
        [ThemeToken::Faint, ThemeToken::Raised],
        [ThemeToken::Text, ThemeToken::Line],
        [ThemeToken::OnAccent, ThemeToken::Accent],
    ];
}

it('measures contrast as WCAG does', function (): void {
    expect(contrast('#000000', '#FFFFFF'))->toBe(21.0)
        ->and(round(contrast('#767676', '#FFFFFF'), 2))->toBe(4.54)
        ->and(round(contrast('#777777', '#FFFFFF'), 2))->toBe(4.48);
});

it('answers every role with a hex the parser can paint, in both themes', function (WhoseTheme $theme): void {
    $malformed = [];

    foreach (ThemeToken::cases() as $token) {
        $hex = $token->in($theme);

        if (preg_match('/^#[0-9A-F]{6}$/', $hex) !== 1) {
            $malformed[] = sprintf('%s answers %s', $token->value, $hex);
        }
    }

    expect($malformed)->toBe([], implode("\n", $malformed));
})->with(WhoseTheme::cases());

it('sets every text pairing at AA or better, in both themes', function (WhoseTheme $theme): void {
    $short = [];

    foreach (pairingsSetAsText() as [$text, $ground]) {
        $ratio = contrast($text->in($theme), $ground->in($theme));

        if ($ratio < 4.5) {
            $short[] = sprintf('%s on %s: %.2f', $text->value, $ground->value, $ratio);
        }
    }

    expect($short)->toBe([], sprintf(
        "These pairings fall below WCAG AA (4.5:1):\n  %s\n\nEvery text-on-surface pairing "
        . 'this surface uses must meet AA in both themes.',
        implode("\n  ", $short),
    ));
})->with(WhoseTheme::cases());

it('sets as text only the roles that are foregrounds', function (): void {
    expect(ThemeToken::Text->safeAsText())->toBeTrue()
        ->and(ThemeToken::Muted->safeAsText())->toBeTrue()
        ->and(ThemeToken::Faint->safeAsText())->toBeTrue()
        ->and(ThemeToken::OnAccent->safeAsText())->toBeTrue()
        ->and(ThemeToken::Accent->safeAsText())->toBeFalse()
        ->and(ThemeToken::Surface->safeAsText())->toBeFalse()
        ->and(ThemeToken::Raised->safeAsText())->toBeFalse()
        ->and(ThemeToken::Line->safeAsText())->toBeFalse();
});

it('paints the member on the ink theme\'s canvas and the operator on its ink', function (): void {
    expect(ThemeToken::Surface->in(WhoseTheme::Member))->toBe('#100F0A')
        ->and(ThemeToken::Surface->in(WhoseTheme::Operator))->toBe('#17160F');
});

it('sets the member\'s text in paper and text-muted alone, and only the operator\'s in text-faint', function (): void {
    expect(ThemeToken::Faint->in(WhoseTheme::Member))->toBe(ThemeToken::Muted->in(WhoseTheme::Member))
        ->and(ThemeToken::Faint->in(WhoseTheme::Operator))->toBe('#8D8972');
});

it('tells the two themes apart by the ground and the faintest text alone', function (): void {
    $differ = [];

    foreach (ThemeToken::cases() as $role) {
        if ($role->in(WhoseTheme::Member) !== $role->in(WhoseTheme::Operator)) {
            $differ[] = $role;
        }
    }

    expect($differ)->toBe([ThemeToken::Surface, ThemeToken::Faint]);
});

it('keeps lemon as the accent with ink on it, in both themes', function (WhoseTheme $theme): void {
    expect(ThemeToken::Accent->in($theme))->toBe('#F0C419')
        ->and(ThemeToken::OnAccent->in($theme))->toBe('#17160F');
})->with(WhoseTheme::cases());

it('resolves no name it was not given', function (): void {
    expect(ThemeToken::tryFrom('background'))->toBeNull()
        ->and(ThemeToken::tryFrom('on-surface'))->toBeNull()
        ->and(ThemeToken::tryFrom('primary'))->toBeNull();
});
