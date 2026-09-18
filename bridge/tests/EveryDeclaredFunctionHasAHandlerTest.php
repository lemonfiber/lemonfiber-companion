<?php

declare(strict_types=1);

// The manifest is a promise, and this is what holds it to one.
//
// `nativephp.json` is what the NativePHP builder reads when it writes the
// registration file each platform compiles. Every entry there becomes one line
// of generated Kotlin and one of generated Swift naming a symbol, and the
// builder does not look at whether the symbol is there — so an entry naming
// something that does not exist reaches the phone as a function the router has
// no handler for. The operator taps a control and nothing happens; the log line
// that says why is on a device nobody is watching.
//
// Checked here rather than at call time, and rather than by the native
// compilers. Both of those find it eventually — Kotlin will not resolve the
// reference, and Swift will not either — but they find it after a toolchain, a
// Gradle run and a device, which is minutes away from the edit and is not run
// at all on a change that touches no native source. This runs in the same
// second as every other suite and names what is missing.
//
// It reads the sources as text. A declaration is a line, the files are small,
// and the alternative is a Kotlin parser and a Swift parser in PHP — which
// would be two more things able to go quiet.

/**
 * The plugin's manifest, whole.
 *
 * `CallTest` reads the same file for the names alone and declares its own
 * reader; this one needs the symbols beside them. Two readers rather than one
 * shared helper because a helper declared in one test file is defined only in
 * the process that loaded it, and the suite runs in parallel — a cross-file
 * call would be an undefined function on whichever worker drew the other file.
 * Named for this package: a test file's helpers land in the global namespace
 * beside every other root suite's (`G10`).
 *
 * @return array{
 *     namespace: string,
 *     bridge_functions: list<array{name: string, android?: string, ios?: string}>,
 *     android?: array{init_function?: string},
 *     ios?: array{init_function?: string},
 * }
 */
function theBridgeManifest(): array
{
    /**
     * @var array{
     *     namespace: string,
     *     bridge_functions: list<array{name: string, android?: string, ios?: string}>,
     *     android?: array{init_function?: string},
     *     ios?: array{init_function?: string},
     * } $said
     */
    $said = json_decode(
        (string) file_get_contents(sprintf('%s/nativephp.json', dirname(__DIR__))),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    return $said;
}

/**
 * Every symbol the manifest names on one platform, against where it was named.
 *
 * The key is what the failure has to print — a bare symbol tells a reader which
 * word is wrong and not which promise it breaks.
 *
 * @return array<string, string> symbol path => what declares it
 */
function whatTheManifestNamesOn(string $platform): array
{
    $manifest = theBridgeManifest();
    $named = [];

    foreach ($manifest['bridge_functions'] as $function) {
        $symbol = $function[$platform] ?? null;

        if (is_string($symbol)) {
            $named[$symbol] = $function['name'];
        }
    }

    $init = $manifest[$platform]['init_function'] ?? null;

    if (is_string($init)) {
        $named[$init] = sprintf('the %s init function', $platform);
    }

    return $named;
}

/**
 * The file that would hold a symbol, and the declaration that would be in it.
 *
 * A symbol path is `[package.]Holder.Member` on both platforms: the builder
 * writes `Holder.Member(...)` into the registration file, so the holder is what
 * the import resolves and the member is what is constructed or called. The file
 * is named after the holder because that is how this plugin ships its sources —
 * flat, one file per holder, which is the layout the builder copies.
 *
 * @return array{0: string, 1: string, 2: string} the holder, the member, the package
 */
function whereASymbolWouldBe(string $symbol): array
{
    $parts = explode('.', $symbol);
    $member = array_pop($parts);
    $holder = array_pop($parts) ?? '';

    return [$holder, $member, implode('.', $parts)];
}

/**
 * Whether a source declares a type or a function by that name.
 *
 * Both languages are read with the same two shapes because both declare the
 * same two things here: the handler is a type and an init function is a
 * function. A declaration is anchored to the start of its line, so a name
 * inside a comment or a string is not one — which is the whole reason this is
 * a pattern rather than `str_contains`.
 */
function whatASourceDeclares(string $source, string $name): bool
{
    $word = preg_quote($name, '/');

    $type = sprintf(
        '/^\s*(?:@objc\s+)?(?:public|internal|private|fileprivate|open)?\s*'
        . '(?:final\s+|data\s+|sealed\s+|abstract\s+|value\s+)*(?:class|object|struct|enum)\s+%s\b/m',
        $word,
    );

    $function = sprintf(
        '/^\s*(?:@objc\s+|@JvmStatic\s+)*(?:public|internal|private|fileprivate|open)?\s*'
        . '(?:static\s+|final\s+)*fun(?:c)?\s+%s\b/m',
        $word,
    );

    return preg_match($type, $source) === 1 || preg_match($function, $source) === 1;
}

/**
 * Every symbol the manifest names on one platform that the sources do not hold.
 *
 * @param array<string, string> $named
 *
 * @return list<string>
 */
function whatNothingAnswers(array $named, string $under, string $extension): array
{
    $missing = [];

    foreach ($named as $symbol => $declaredBy) {
        [$holder, $member, $package] = whereASymbolWouldBe($symbol);

        $path = sprintf('%s/%s.%s', $under, $holder, $extension);
        $source = is_file($path) ? file_get_contents($path) : false;

        if (! is_string($source)) {
            $missing[] = sprintf('%s (%s) — no %s.%s', $symbol, $declaredBy, $holder, $extension);

            continue;
        }

        if (! whatASourceDeclares($source, $holder)) {
            $missing[] = sprintf('%s (%s) — %s.%s declares no %s', $symbol, $declaredBy, $holder, $extension, $holder);

            continue;
        }

        if (! whatASourceDeclares($source, $member)) {
            $missing[] = sprintf('%s (%s) — %s.%s declares no %s', $symbol, $declaredBy, $holder, $extension, $member);

            continue;
        }

        // Kotlin carries a package and the registration file imports it, so a
        // file moved to a package the manifest does not name is a symbol that
        // does not resolve even though the declaration is right there.
        if ($package !== '' && preg_match(sprintf('/^package\s+%s\s*$/m', preg_quote($package, '/')), $source) !== 1) {
            $missing[] = sprintf('%s (%s) — %s.%s is not in package %s', $symbol, $declaredBy, $holder, $extension, $package);
        }
    }

    sort($missing);

    return $missing;
}

/**
 * Every handler one platform's sources hold, as the manifest would have to name it.
 *
 * The other direction of the same promise. A handler written and never declared
 * is not a crash — it is a function the router has never heard of, which is the
 * same silence from the other end.
 *
 * @return list<string>
 */
function whatTheSourcesHoldOn(string $under, string $extension, string $interface, string $package = ''): array
{
    $held = [];

    $paths = glob(sprintf('%s/*.%s', $under, $extension));

    foreach ($paths === false ? [] : $paths as $path) {
        $source = file_get_contents($path);

        if (! is_string($source)) {
            continue;
        }

        $holder = basename($path, sprintf('.%s', $extension));
        $found = [];

        preg_match_all(
            sprintf('/^\s+(?:public\s+|internal\s+)?(?:final\s+)?class\s+(\w+)[^\n]*\b%s\b/m', preg_quote($interface, '/')),
            $source,
            $found,
        );

        foreach ($found[1] as $member) {
            $held[] = sprintf('%s%s.%s', $package, $holder, $member);
        }
    }

    sort($held);

    return $held;
}

it('carries an Android handler for every function the manifest declares', function (): void {
    $missing = whatNothingAnswers(
        whatTheManifestNamesOn('android'),
        sprintf('%s/resources/android', dirname(__DIR__)),
        'kt',
    );

    expect($missing)->toBe([], sprintf(
        "The manifest names these on Android and the sources do not hold them:\n  %s\n\n"
        . 'The builder writes each one into the registration file it generates without '
        . 'looking, so this reaches a handset as a function the router cannot find — a '
        . 'control that does nothing, and a log line on a device nobody is watching. '
        . 'Write the handler or take the entry out.',
        implode("\n  ", $missing),
    ));
});

it('carries an iOS handler for every function the manifest declares', function (): void {
    $missing = whatNothingAnswers(
        whatTheManifestNamesOn('ios'),
        sprintf('%s/resources/ios', dirname(__DIR__)),
        'swift',
    );

    expect($missing)->toBe([], sprintf(
        "The manifest names these on iOS and the sources do not hold them:\n  %s\n\n"
        . 'The builder writes each one into the registration file it generates without '
        . 'looking, so this reaches a phone as a function the router cannot find. Write '
        . 'the handler or take the entry out.',
        implode("\n  ", $missing),
    ));
});

it('declares every handler the sources hold', function (): void {
    $declared = array_values(array_unique(array_filter(
        array_merge(
            array_column(theBridgeManifest()['bridge_functions'], 'android'),
            array_column(theBridgeManifest()['bridge_functions'], 'ios'),
        ),
        static fn(string $symbol): bool => $symbol !== '',
    )));

    sort($declared);

    $held = array_merge(
        whatTheSourcesHoldOn(
            sprintf('%s/resources/android', dirname(__DIR__)),
            'kt',
            'BridgeFunction',
            'app.lemonfiber.native.',
        ),
        whatTheSourcesHoldOn(
            sprintf('%s/resources/ios', dirname(__DIR__)),
            'swift',
            'BridgeFunction',
        ),
    );

    $undeclared = array_values(array_filter(
        $held,
        static fn(string $symbol): bool => ! in_array($symbol, $declared, strict: true),
    ));

    expect($undeclared)->toBe([], sprintf(
        "These handlers exist and the manifest names none of them:\n  %s\n\n"
        . 'Nothing registers a handler the manifest does not declare, so it is a function '
        . 'the router has never heard of — the same silence as a missing one, reached from '
        . 'the other end. Declare it or delete it.',
        implode("\n  ", $undeclared),
    ));
});

it('names a symbol that is not there', function (): void {
    // The check's own violation, handed to the check. Every assertion above
    // answers `[]` for a manifest that agrees with its sources, and so would a
    // reader that resolved nothing at all — which is the shape of the vacuous
    // check this repository keeps finding in its own rules.
    $missing = whatNothingAnswers(
        ['app.lemonfiber.native.LemonfiberFunctions.Vanished' => 'Lemonfiber.Vanished'],
        sprintf('%s/resources/android', dirname(__DIR__)),
        'kt',
    );

    expect($missing)->toHaveCount(1)
        ->and($missing[0])->toContain('Vanished');

    // A file that is not there and a declaration that is not in it are told
    // apart, because they send a reader to two different places.
    expect(whatNothingAnswers(
        ['app.lemonfiber.native.NoSuchHolder.Anything' => 'Lemonfiber.Nothing'],
        sprintf('%s/resources/android', dirname(__DIR__)),
        'kt',
    ))->toBe(['app.lemonfiber.native.NoSuchHolder.Anything (Lemonfiber.Nothing) — no NoSuchHolder.kt']);

    // And a package the manifest got wrong, which resolves to a declaration
    // that is right there and an import that finds nothing.
    expect(whatNothingAnswers(
        ['app.lemonfiber.elsewhere.LemonfiberFunctions.Conceal' => 'Lemonfiber.Conceal'],
        sprintf('%s/resources/android', dirname(__DIR__)),
        'kt',
    ))->toHaveCount(1);
});

it('reads a declaration and not a mention of one', function (): void {
    // What makes the patterns worth having rather than `str_contains`. A name
    // in a comment, in a doc comment or inside a string is how a check like
    // this reports a handler that is not there.
    expect(whatASourceDeclares("// class Ghost\n", 'Ghost'))->toBeFalse()
        ->and(whatASourceDeclares(" * class Ghost is gone\n", 'Ghost'))->toBeFalse()
        ->and(whatASourceDeclares("    public class Ghost(private val a: A) : BridgeFunction {\n", 'Ghost'))->toBeTrue()
        ->and(whatASourceDeclares("    class Ghost: BridgeFunction {\n", 'Ghost'))->toBeTrue()
        ->and(whatASourceDeclares("public object Ghost {\n", 'Ghost'))->toBeTrue()
        ->and(whatASourceDeclares("enum Ghost {\n", 'Ghost'))->toBeTrue()
        ->and(whatASourceDeclares("    @objc public static func Ghost() {\n", 'Ghost'))->toBeTrue()
        ->and(whatASourceDeclares("    @JvmStatic\n    public fun Ghost(a: A) {\n", 'Ghost'))->toBeTrue()
        // A longer name that starts with the one being looked for is a
        // different symbol, and the word boundary is what keeps them apart.
        ->and(whatASourceDeclares("public object GhostWriter {\n", 'Ghost'))->toBeFalse();
});

it('finds the handlers a source holds', function (): void {
    $held = whatTheSourcesHoldOn(
        sprintf('%s/resources/android', dirname(__DIR__)),
        'kt',
        'BridgeFunction',
        'app.lemonfiber.native.',
    );

    // Named rather than counted: a reader that answered `[]` would pass every
    // assertion in the third test above, and would pass it by finding nothing.
    expect($held)->toContain('app.lemonfiber.native.LemonfiberFunctions.Conceal')
        ->and($held)->toContain('app.lemonfiber.native.LemonfiberAuth.CanAuthenticate');

    $ios = whatTheSourcesHoldOn(sprintf('%s/resources/ios', dirname(__DIR__)), 'swift', 'BridgeFunction');

    expect($ios)->toContain('LemonfiberFunctions.IsProtected')
        ->and($ios)->toContain('LemonfiberAuth.Authenticate');
});
