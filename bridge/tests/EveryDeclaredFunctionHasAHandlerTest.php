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
//
// It also reads what the manifest *says*, because it is the only thing that
// reads the manifest at all. `NoRequirementIdInACommentTest` walks PHP and
// matches the four markers this repository writes a comment with; JSON has
// none of them, so a description carrying a requirement identifier sat outside
// every rule there is. A description is read by people — and by anybody who
// takes this plugin from the marketplace the licence points at, for whom an
// identifier names a requirement in a specification they cannot open.

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
 *     bridge_functions: list<array{name: string, android?: string, ios?: string, description?: string}>,
 *     android?: array{init_function?: string},
 *     ios?: array{init_function?: string},
 * }
 */
function theBridgeManifest(): array
{
    /**
     * @var array{
     *     namespace: string,
     *     bridge_functions: list<array{name: string, android?: string, ios?: string, description?: string}>,
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
 * Whether a symbol names a top-level function rather than a member of a type.
 *
 * By the case of the segment *before* the last one, which is the convention
 * both languages use and neither enforces: a type is `Holder` and a package
 * segment is `lowercase`. The last segment is no help — `LemonfiberInit.install`
 * and `app.lemonfiber.native.installLemonfiber` both end in a lowercase name,
 * and only one of them is a member of something.
 *
 * A symbol with no segment before the last is a bare function name, which is
 * the same answer for the same reason: there is no holder in it.
 *
 * An Android `init_function` is the case that needs this. It is
 * `package.function`, one segment shorter than the `package.Class.Method` a
 * handler is, because the generated registration calls it with the context it
 * was handed and a member of an object is not reachable from there.
 */
function readsAsATopLevelFunction(string $symbol): bool
{
    $parts = explode('.', $symbol);

    array_pop($parts);

    $holder = array_pop($parts);

    if (! is_string($holder) || $holder === '') {
        return true;
    }

    return mb_strtolower(mb_substr($holder, 0, 1)) === mb_substr($holder, 0, 1);
}

/**
 * The file that would hold a symbol, and the declaration that would be in it.
 *
 * A handler's symbol path is `[package.]Holder.Member` on both platforms: the
 * builder writes `Holder.Member(...)` into the registration file, so the holder
 * is what the import resolves and the member is what is constructed or called.
 * The file is named after the holder because that is how this plugin ships its
 * sources — flat, one file per holder, which is the layout the builder copies.
 *
 * A top-level function has no holder, so it has no file this can name: Kotlin
 * does not tie a top-level declaration to a filename, and `LemonfiberInit.kt`
 * holds `installLemonfiber`. The holder comes back empty and
 * {@see whatNothingAnswers()} searches the tree instead of guessing a path.
 *
 * @return array{0: string, 1: string, 2: string} the holder, the member, the package
 */
function whereASymbolWouldBe(string $symbol): array
{
    $parts = explode('.', $symbol);
    $member = array_pop($parts);

    if (readsAsATopLevelFunction($symbol)) {
        return ['', $member, implode('.', $parts)];
    }

    $holder = array_pop($parts) ?? '';

    return [$holder, $member, implode('.', $parts)];
}

/**
 * Every source on one platform, by path.
 *
 * What a symbol with no holder has to be looked for in. Reading the tree is
 * more work than opening one predicted file and it is the only honest answer
 * for a declaration whose file the symbol does not name.
 *
 * @return array<string, string> path => contents
 */
function everySourceUnder(string $under, string $extension): array
{
    $found = [];
    $paths = glob(sprintf('%s/*.%s', $under, $extension));

    foreach ($paths === false ? [] : $paths as $path) {
        $source = file_get_contents($path);

        if (is_string($source)) {
            $found[$path] = $source;
        }
    }

    return $found;
}

/**
 * The source that declares a top-level function, or nothing.
 *
 * @param array<string, string> $sources
 */
function whatDeclaresTopLevel(array $sources, string $member): ?string
{
    foreach ($sources as $source) {
        if (whatASourceDeclares($source, $member)) {
            return $source;
        }
    }

    return null;
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
        $missing = [...$missing, ...whatOneSymbolIsMissing($symbol, $declaredBy, $under, $extension)];
    }

    sort($missing);

    return $missing;
}

/**
 * What one symbol the manifest names is missing, or nothing because it is there.
 *
 * One symbol per call so that the two shapes — a member of a type, and a
 * top-level function with no file the symbol names — are written apart from
 * each other rather than as branches of one loop a reader has to hold.
 *
 * @return list<string>
 */
function whatOneSymbolIsMissing(string $symbol, string $declaredBy, string $under, string $extension): array
{
    [$holder, $member, $package] = whereASymbolWouldBe($symbol);

    if ($holder === '') {
        $found = whatDeclaresTopLevel(everySourceUnder($under, $extension), $member);

        return is_string($found)
            ? whereAPackageIsWrong($found, $symbol, $declaredBy, $package)
            : [sprintf('%s (%s) — no .%s declares %s', $symbol, $declaredBy, $extension, $member)];
    }

    $path = sprintf('%s/%s.%s', $under, $holder, $extension);
    $source = is_file($path) ? file_get_contents($path) : false;

    if (! is_string($source)) {
        return [sprintf('%s (%s) — no %s.%s', $symbol, $declaredBy, $holder, $extension)];
    }

    $absent = whatAHolderDoesNotDeclare($source, $symbol, $declaredBy, $holder, $member, $extension);

    return $absent === [] ? whereAPackageIsWrong($source, $symbol, $declaredBy, $package) : $absent;
}

/**
 * Which of the holder and the member a source does not declare.
 *
 * Both are asked because they fail differently: a file that declares no holder
 * is a file the builder's import will not resolve, and one that declares the
 * holder and not the member is an import that resolves to something with no
 * such call on it. A reader given only "missing" has to open the file to find
 * out which.
 *
 * @return list<string>
 */
function whatAHolderDoesNotDeclare(
    string $source,
    string $symbol,
    string $declaredBy,
    string $holder,
    string $member,
    string $extension,
): array {
    $absent = [];

    foreach ([$holder, $member] as $name) {
        if (! whatASourceDeclares($source, $name)) {
            $absent[] = sprintf('%s (%s) — %s.%s declares no %s', $symbol, $declaredBy, $holder, $extension, $name);
        }
    }

    return $absent;
}

/**
 * Whether a source sits in the package the manifest said it did.
 *
 * Kotlin carries a package and the registration file imports it, so a file
 * moved to a package the manifest does not name is a symbol that does not
 * resolve even though the declaration is right there. Its own function because
 * both branches above need it and the one for a top-level function has no
 * filename to put in the message.
 *
 * @return list<string>
 */
function whereAPackageIsWrong(string $source, string $symbol, string $declaredBy, string $package): array
{
    if ($package === '') {
        return [];
    }

    return preg_match(sprintf('/^package\s+%s\s*$/m', preg_quote($package, '/')), $source) === 1
        ? []
        : [sprintf('%s (%s) — what declares it is not in package %s', $symbol, $declaredBy, $package)];
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
    // A floor before the comparison, stated rather than left to the two tests
    // below to imply. Every assertion here answers `[]` for a manifest that
    // agrees with its sources, and a manifest with no entries at all would too
    // — having compared nothing.
    expect(theBridgeManifest()['bridge_functions'])->not->toBeEmpty();

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
    expect(theBridgeManifest()['bridge_functions'])->not->toBeEmpty();

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

it('says what each function does without naming a requirement', function (): void {
    // A requirement identifier gestures at a page rather than saying anything,
    // and rots the moment that page is superseded — silently, because nothing
    // reads a description. What is worth keeping is the sentence, which says
    // what the function does; the number belongs on a page a link can reach.
    $named = [];

    foreach (theBridgeManifest()['bridge_functions'] as $function) {
        if (! array_key_exists('description', $function)) {
            continue;
        }

        if (preg_match('/\b[A-Z]+\d*-R\d+\b/', $function['description']) === 1) {
            $named[] = sprintf('%s — %s', $function['name'], $function['description']);
        }
    }

    expect($named)->toBe([], sprintf(
        "These descriptions name a requirement:\n  %s\n\n"
        . 'A manifest description is read by people, and by anybody who takes this plugin '
        . 'from the marketplace its licence points at — for whom the identifier names a '
        . "requirement in a specification they cannot open. Say what the function does, in "
        . 'words that stand on their own.',
        implode("\n  ", $named),
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

it('names a description that gestures at a page', function (): void {
    // The rule above answers `[]` for a manifest that says what it does, and
    // so would one that read no descriptions at all. This is the reading of
    // the pattern itself, so the check cannot pass by finding nothing.
    expect(preg_match('/\b[A-Z]+\d*-R\d+\b/', 'Protect the window from capture (N4-R18)'))->toBe(1)
        ->and(preg_match('/\b[A-Z]+\d*-R\d+\b/', 'Protect the window from capture'))->toBe(0);
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

/** The platforms this bridge ships a half for. */
const HALVES_THIS_BRIDGE_SHIPS = ['android', 'ios'];

/**
 * The symbol one platform's half is switched on by, or nothing.
 *
 * Takes the manifest rather than reading it, so the judgement can be driven
 * with a manifest that is missing the key, one whose platform section is not an
 * object at all, and one that has the symbol — none of which can be arranged by
 * editing the real file without leaving it wrong for the length of a run.
 *
 * @param array<mixed> $manifest
 */
function whatSwitchesOn(array $manifest, string $platform): string
{
    $half = $manifest[$platform] ?? [];
    $symbol = is_array($half) ? ($half['init_function'] ?? '') : '';

    return is_string($symbol) ? $symbol : '';
}

it('names the symbol that switches each half on', function (): void {
    // A native half is installed by one symbol the builder calls at startup. The
    // failure when it is missing is silent, which is the whole reason this is a
    // rule: the builder emits an import and a call for whatever the manifest
    // names and emits nothing at all for what it does not. There is no error to
    // read — the plugin compiles, every bridge function registers and answers,
    // every screen renders, and the one call that had to happen first never
    // does.
    //
    // Asked of the two platforms together rather than once each, because the
    // shape of this failure is asymmetry: they want different shapes — Android a
    // top-level function taking a `Context`, iOS a class with a static method —
    // and a shape that does not fit is easier to leave out than to convert. A
    // rule written per platform would also be satisfied by a manifest that
    // switches neither half on.
    $manifest = theBridgeManifest();

    $missing = array_values(array_filter(
        HALVES_THIS_BRIDGE_SHIPS,
        static fn(string $platform): bool => whatSwitchesOn($manifest, $platform) === '',
    ));

    expect($missing)->toBe([], sprintf(
        "These platforms name no `init_function`: %s.\n\n"
        . 'The builder emits an import and a call for whatever the manifest names and nothing '
        . 'for what it does not, so a half nobody switches on fails without saying so: the '
        . 'lifecycle callbacks are never registered, every screen still renders, and the task '
        . "switcher quietly holds the last frame.\n"
        . 'Android wants a top-level function taking a `Context`, iOS a class with a static '
        . 'method. Declare the missing one under `<platform>.init_function`.',
        implode(', ', $missing),
    ));
});

it('tells a declared half from an undeclared one', function (): void {
    expect(whatSwitchesOn(['android' => ['init_function' => 'app.example.install']], 'android'))
        ->toBe('app.example.install');

    // Every way a platform can fail to name one, because each is a different
    // shape of nothing and a reader handling two of the three would pass this
    // rule on a manifest it could not understand.
    expect(whatSwitchesOn(['android' => ['min_version' => 26]], 'android'))->toBe('');
    expect(whatSwitchesOn(['android' => 'not an object'], 'android'))->toBe('');
    expect(whatSwitchesOn(['android' => ['init_function' => []]], 'android'))->toBe('');
    expect(whatSwitchesOn([], 'ios'))->toBe('');
});

it('tells a top-level function from a member of a type', function (): void {
    // The distinction the resolver above rests on, driven directly. The last
    // segment cannot answer it — `LemonfiberInit.install` and
    // `app.lemonfiber.native.installLemonfiber` both end in a lowercase name and
    // only one is a member of something — so it is the segment before it that
    // decides, and getting that backwards sends the reader looking for a file
    // called `native.kt`.
    expect(readsAsATopLevelFunction('app.lemonfiber.native.installLemonfiber'))->toBeTrue();
    expect(readsAsATopLevelFunction('installLemonfiber'))->toBeTrue();

    expect(readsAsATopLevelFunction('LemonfiberInit.install'))->toBeFalse();
    expect(readsAsATopLevelFunction('app.lemonfiber.native.LemonfiberFunctions.Conceal'))->toBeFalse();

    // And the holder it answers with, which is what names the file to open.
    expect(whereASymbolWouldBe('app.lemonfiber.native.installLemonfiber'))
        ->toBe(['', 'installLemonfiber', 'app.lemonfiber.native']);
    expect(whereASymbolWouldBe('LemonfiberInit.install'))->toBe(['LemonfiberInit', 'install', '']);
});
