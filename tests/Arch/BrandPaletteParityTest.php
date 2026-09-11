<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\Tree;

// The palette is copied, so the copy is checked.
//
// `ThemeToken::hex()` writes two hexes out in PHP because the module may not
// read a file — B3 keeps the filesystem in an adapter, and A9 keeps a read out
// of boot, where the resolver is registered. That leaves a second copy of two
// brand values, which is a thing that drifts, so it is checked against the
// first on every run. The brand repository makes exactly this arrangement for
// `tokens.css`, which is hand-maintained and checked against `tokens.json` by
// `brand:scripts/check_tokens.py` for the same reason.
//
// The file it checks against is a distribution artefact: `shared/assets.sha256`
// in the spec repository records its digest with `brand:tokens/tokens.json` as
// its home, so the hygiene gate fails the moment this copy stops being
// byte-identical to the brand's own. Two gates in series — the vendored file
// matches brand, and the PHP matches the vendored file — and neither of them
// is a person remembering.

/**
 * Which brand colour each theme token asserts.
 *
 * This is the brand decision itself, from `60-brand/surface-mapping.md`, and it
 * is written here rather than derived from the enum so that the test has
 * something of its own to compare against. Deriving it would only re-state the
 * code under test back to itself.
 *
 * @return array<string, string> theme token => brand token
 */
function assertedBrandColours(): array
{
    return [
        ThemeToken::Accent->value => 'lemon',
        ThemeToken::OnAccent->value => 'ink',
    ];
}

/**
 * The brand's own colours, as the vendored token file carries them.
 *
 * @return array<string, string>
 */
function brandColours(): array
{
    $path = Tree::at('app-modules/design/resources/tokens.json');
    $raw = file_get_contents($path);

    if (! is_string($raw)) {
        throw new RuntimeException(sprintf(
            '%s could not be read. It is the brand\'s token file, vendored here so the '
            . 'two hexes this application asserts can be checked against it — without it '
            . 'this rule checks nothing.',
            $path,
        ));
    }

    /** @var mixed $decoded */
    $decoded = json_decode($raw, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
    $colours = is_array($decoded) ? $decoded['color'] ?? null : null;

    if (! is_array($colours) || $colours === []) {
        throw new RuntimeException(sprintf('%s carries no colours', $path));
    }

    $found = [];

    foreach ($colours as $name => $value) {
        if (is_string($name) && is_string($value)) {
            $found[$name] = $value;
        }
    }

    return $found;
}

it('DES-R24 — every asserted colour is still the brand\'s', function (): void {
    $brand = brandColours();
    $drifted = [];

    $stated = assertedBrandColours();

    // Driven off the cases rather than off the table, so that the lookup cannot
    // raise on a token the table names and the enum does not have. A case the
    // table has no row for is the other rule's finding, reported there by name.
    foreach (ThemeToken::cases() as $case) {
        $name = $stated[$case->value] ?? null;

        if ($name === null) {
            continue;
        }

        $token = $case->value;
        $expected = $brand[$name] ?? null;

        if ($expected === null) {
            $drifted[] = sprintf('%s claims brand `%s`, which the token file does not have', $token, $name);

            continue;
        }

        if (strtoupper($case->hex()) !== strtoupper($expected)) {
            $drifted[] = sprintf(
                '%s answers %s, but brand `%s` is %s',
                $token,
                $case->hex(),
                $name,
                $expected,
            );
        }
    }

    expect($drifted)->toBe([], sprintf(
        "These no longer paint what the brand says they paint:\n  %s\n\n"
        . 'The hexes in ThemeToken are a copy of two values the brand owns, kept in PHP '
        . 'because the module may not read a file (B3, A9). Take the value from '
        . "app-modules/design/resources/tokens.json, which is the brand's own file.\n"
        . 'If the brand changed, the vendored copy is refreshed first — the hygiene gate '
        . 'checks it against brand:tokens/tokens.json by digest — and this follows.',
        implode("\n  ", $drifted),
    ));
});

it('DES-R24 — no token asserts a colour nobody wrote down', function (): void {
    $stated = assertedBrandColours();

    $unstated = array_values(array_filter(
        array_map(static fn(ThemeToken $token): string => $token->value, ThemeToken::cases()),
        static fn(string $token): bool => ! array_key_exists($token, $stated),
    ));

    expect($unstated)->toBe([], sprintf(
        "These tokens paint a hex nothing checks:\n  %s\n\n"
        . 'A case added to ThemeToken without a row in assertedBrandColours() carries a '
        . 'hex that no longer has to match anything the brand says. Name the brand '
        . "colour it asserts, and the check above covers it too.\nIf it asserts no brand "
        . 'colour, it is something the platform should be deciding — which is the whole '
        . 'of what surface-mapping.md says about this app (DES-R24, DES-R26).',
        implode("\n  ", $unstated),
    ));
});
