<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\Tree;

// The palette is copied, so the copy is checked.
//
// `ThemeToken` writes its hexes out in PHP because the module may not read a
// file: B3 keeps the filesystem in an adapter, and A9 keeps a read out of boot,
// where the resolvers are registered. So each is checked against the brand's
// own token file on every run. That file is a distribution copy, which the spec
// repository's `shared/assets.sha256` holds byte-identical to
// `brand:tokens/tokens.json`.

/**
 * Which brand colour each role asserts, in the member's theme and in the operator's.
 *
 * The brand decision itself, from `60-brand/surface-mapping.md`, written here
 * rather than derived from the enum so the test has something of its own to
 * compare against. A name prefixed `ink:` is read from the ink theme; an
 * unprefixed one from the brand's core colours.
 *
 * @return array<string, array{string, string}> role => [member, operator]
 */
function assertedBrandColours(): array
{
    return [
        ThemeToken::Accent->value => ['lemon', 'lemon'],
        ThemeToken::OnAccent->value => ['ink', 'ink'],
        ThemeToken::Surface->value => ['ink:canvas', 'ink:paper'],
        ThemeToken::Raised->value => ['ink:pith', 'ink:pith'],
        ThemeToken::Text->value => ['ink:text', 'ink:text'],
        ThemeToken::Muted->value => ['ink:text-muted', 'ink:text-muted'],
        ThemeToken::Faint->value => ['ink:text-muted', 'ink:text-faint'],
        ThemeToken::Line->value => ['ink:line', 'ink:line'],
        ThemeToken::Ok->value => ['ink:text', 'ink:ok'],
        ThemeToken::Warn->value => ['ink:text', 'ink:fiber'],
        ThemeToken::Alarm->value => ['ink:text', 'ink:alarm'],
        ThemeToken::Activity->value => ['ink:text', 'ink:fiber'],
        ThemeToken::WarnTint->value => ['ink:pith', 'ink:warn-tint'],
        ThemeToken::AlarmTint->value => ['ink:pith', 'ink:alarm-tint'],
    ];
}

/**
 * The brand's colours as the vendored token file carries them: the core colours
 * by name, and the ink theme's as `ink:<name>`.
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
            . 'hexes this application asserts can be checked against it.',
            $path,
        ));
    }

    /** @var mixed $decoded */
    $decoded = json_decode($raw, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
    $colours = data_get($decoded, 'color');
    $ink = data_get($decoded, 'theme.ink');

    if (! is_array($colours) || $colours === [] || ! is_array($ink) || $ink === []) {
        throw new RuntimeException(sprintf('%s carries no colours, or no ink theme', $path));
    }

    return [...namedHexes($colours, ''), ...namedHexes($ink, 'ink:')];
}

/**
 * The string-keyed string values of one block of the token file, each key
 * prefixed.
 *
 * @param array<array-key, mixed> $block
 *
 * @return array<string, string>
 */
function namedHexes(array $block, string $prefix): array
{
    $found = [];

    foreach ($block as $name => $value) {
        if (is_string($name) && is_string($value)) {
            $found[sprintf('%s%s', $prefix, $name)] = $value;
        }
    }

    return $found;
}

it('paints every role with the brand colour it claims, in both themes', function (): void {
    $brand = brandColours();
    $stated = assertedBrandColours();
    $drifted = [];

    foreach (ThemeToken::cases() as $case) {
        if (! array_key_exists($case->value, $stated)) {
            continue;
        }

        [$memberName, $operatorName] = $stated[$case->value];

        foreach ([[$memberName, WhoseTheme::Member], [$operatorName, WhoseTheme::Operator]] as [$name, $theme]) {
            $hex = $case->in($theme);

            if (! array_key_exists($name, $brand)) {
                $drifted[] = sprintf('%s claims brand `%s` in the %s theme, which the token file does not have', $case->value, $name, $theme->value);

                continue;
            }

            if (strtoupper($hex) !== strtoupper($brand[$name])) {
                $drifted[] = sprintf('%s answers %s in the %s theme, but brand `%s` is %s', $case->value, $hex, $theme->value, $name, $brand[$name]);
            }
        }
    }

    expect($drifted)->toBe([], sprintf(
        "These no longer paint what the brand says they paint:\n  %s\n\n"
        . 'Take the value from app-modules/design/resources/tokens.json, the brand\'s own '
        . 'file. If the brand changed, the vendored copy is refreshed first, and this follows.',
        implode("\n  ", $drifted),
    ));
});

it('asserts no colour the brand table does not name', function (): void {
    $stated = assertedBrandColours();

    $unstated = array_values(array_filter(
        array_map(static fn(ThemeToken $token): string => $token->value, ThemeToken::cases()),
        static fn(string $token): bool => ! array_key_exists($token, $stated),
    ));

    expect($unstated)->toBe([], sprintf(
        "These roles paint a hex nothing checks:\n  %s\n\nName the brand colour each "
        . 'asserts in assertedBrandColours(), in both themes.',
        implode("\n  ", $unstated),
    ));
});
