<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function basename;
use function class_exists;
use function enum_exists;
use function glob;

use const GLOB_ONLYDIR;

use function in_array;
use function interface_exists;
use function is_array;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function realpath;

use RuntimeException;

use function simplexml_load_file;

use SimpleXMLElement;

use function sort;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * What this repository calls its own code, read from the file that already says so.
 *
 * `phpunit.xml` names the trees held to a coverage floor and the directory every
 * testsuite runs. That list is the only statement of scope here that cannot be
 * narrowed without also narrowing what is measured, which is what makes it the
 * honest answer to *what is ours* — and it is the answer four other gates had
 * each written out for themselves, differently. The analyser's `paths`, the
 * refactorer's, the namespaces the architecture expectations judge and the file
 * lists the text-scanning rules build are all copies of it, and a copy that
 * loses a tree loses it silently: every rule resting on that list keeps
 * reporting a green tick about the trees it still reads.
 *
 * `R4` is the rule that compares them. This is where the answer they are
 * compared against comes from.
 */
final readonly class OurCode
{
    /**
     * The namespace the composition root publishes.
     *
     * Named because it is the one exemption the framework-coupling rules have to
     * grant: `bootstrap/Composition` is where a port meets an adapter and where
     * a facade is the composition rather than a reach into one, which is the
     * same exemption `phpstan.neon` grants it by path. A constant rather than a
     * string beside each rule, so the two cannot drift apart.
     */
    public const string THE_COMPOSITION_ROOT = 'Bootstrap\Composition';

    /**
     * Directories a walk of this repository stops at.
     *
     * Two kinds. `vendor` and `node_modules` hold somebody else's code;
     * `storage`, `coverage` and `bootstrap/cache` hold what a run wrote rather
     * than what anybody committed. By name rather than by path, because each
     * appears at more than one depth — a module has its own `vendor` the day it
     * is installed on its own.
     *
     * The dot-directories are here belt-and-braces: `glob()` does not match a
     * leading dot, so a walk cannot reach them anyway. `.rule-fixtures` is the
     * one that would cost something if that ever changed — it is where a guards
     * run puts the violations it is about to read.
     *
     * `Fixtures` is deliberately absent. A planted fixture *has* to be read, or
     * the rule it is planted under cannot be watched refusing one.
     */
    private const array WHERE_A_WALK_STOPS = [
        'vendor',
        'node_modules',
        'storage',
        'cache',
        'coverage',
        '.git',
        '.github',
        '.phpstan-cache',
        '.rector-cache',
        '.rule-fixtures',
    ];

    /**
     * Every tree `phpunit.xml` holds to a coverage floor, as it writes them.
     *
     * Patterns rather than directories: `app-modules/*\/src` is what the file
     * says, and expanding it here would make a module added tomorrow depend on
     * this list being re-read rather than on the glob it already matches.
     *
     * @return list<string>
     */
    public static function sourceTrees(): array
    {
        return self::pathsAt('/phpunit/source/include/directory');
    }

    /**
     * Every directory a testsuite runs, as `phpunit.xml` writes them.
     *
     * @return list<string>
     */
    public static function testTrees(): array
    {
        return self::pathsAt('/phpunit/testsuites/testsuite/directory');
    }

    /**
     * The same, reduced to the trees a rule about test files would name.
     *
     * `tests/Arch` and `tests/Feature` are two suites in one tree, and an
     * exemption or a guard is written against the tree rather than against each
     * suite in it. Read from the suites rather than listed, so the day a suite
     * moves out from under `tests/` the answer changes with it.
     *
     * @return list<string>
     */
    public static function testRoots(): array
    {
        $roots = [];

        foreach (self::testTrees() as $tree) {
            $roots[str_starts_with($tree, 'tests/') ? 'tests' : $tree] = true;
        }

        $found = array_keys($roots);
        sort($found);

        return $found;
    }

    /**
     * Every namespace the source trees publish, as the autoloader registers them.
     *
     * Derived rather than listed, which is the whole point: a Pest architecture
     * expectation resolves a string against the registered PSR-4 prefixes, so
     * the set of namespaces the rules judge is exactly the set of prefixes
     * pointing into a tree `phpunit.xml` measures. Written by hand it drifted —
     * `Bootstrap\Composition` and `Lemonfiber\Native` were both missing, and
     * `App` was in, resolving to the formatter's own source in `vendor`.
     *
     * A prefix whose directory holds no PHP is left out: Pest raises rather than
     * passing when asked about a namespace with no classes in it, so an empty
     * module would take the whole suite down rather than be skipped.
     *
     * @return list<string>
     */
    public static function namespaces(): array
    {
        $found = [];

        foreach (self::registeredPrefixes() as $prefix => $directories) {
            if (self::pointsIntoAMeasuredTree($directories)) {
                $found[trim($prefix, '\\')] = true;
            }
        }

        $namespaces = array_keys($found);
        sort($namespaces);

        return $namespaces;
    }

    /**
     * Every directory a testsuite runs, as an absolute path with globs resolved.
     *
     * What Pest's `in()` wants, and what the network guard in `tests/Pest.php`
     * binds itself against. The guard named four of the eight suites, and the
     * one it left out is the one that matters: every adapter that talks to a
     * stack is tested under `app-modules/*\/tests`, where an unmocked read
     * opened a socket to `192.168.1.42` — a private address on whatever network
     * the machine running the suite is on (`R4`, `G3`).
     *
     * @return list<string>
     */
    public static function testDirectories(): array
    {
        $found = [];

        foreach (self::testTrees() as $tree) {
            $found = [...$found, ...self::directoriesMatching($tree)];
        }

        sort($found);

        return $found;
    }

    /**
     * Every PHP file in a tree the coverage floor measures.
     *
     * The production half of {@see phpFiles()}, for the rules that are about
     * what the application does rather than about how it is written. `bridge/src`
     * and `bootstrap/Composition` are both in here and were in neither of the
     * two lists that asked this question before.
     *
     * @return list<string>
     */
    public static function sourceFiles(): array
    {
        $found = [];

        foreach (self::expandedSourceTrees() as $tree) {
            $found = [...$found, ...Tree::filesUnder(Tree::at($tree), '.php')];
        }

        sort($found);

        return $found;
    }

    /**
     * Every class, interface and enum those trees declare, by name.
     *
     * The same question {@see Module::classNames()} answers for one module,
     * asked of everything the coverage floor measures — so `bridge/src` and
     * `bootstrap/Composition` are in it, and a path package added tomorrow is
     * in it the moment `phpunit.xml` measures it. A rule assembling the module
     * list for itself judges the modules and is silent about the two trees
     * beside them, which is the drift `R4` is about.
     *
     * Read from the file rather than from the autoloader's classmap, for the
     * reason `Module::classNames()` gives: the map only exists where the
     * autoloader was dumped optimized, which holds in CI and not always on a
     * laptop.
     *
     * A file whose declared name the autoloader cannot resolve is left out
     * rather than raising. That is a misplacement, `W2` reports it by name, and
     * a rule about ports is not the place to find out.
     *
     * @return list<class-string>
     */
    public static function sourceClasses(): array
    {
        $found = [];

        foreach (self::sourceFiles() as $file) {
            $name = Imports::declaredName($file);

            if ($name !== '' && (class_exists($name) || interface_exists($name) || enum_exists($name))) {
                $found[] = $name;
            }
        }

        sort($found);

        return $found;
    }

    /**
     * Every PHP file this repository owns, wherever it sits.
     *
     * The widest of the answers here and the one the text-scanning rules want.
     * A comment rule, a token scan for a literal, an import check: none of them
     * is about a tree, all of them are about PHP somebody here wrote, and each
     * had its own list of six or seven directories. `config/` and `scripts/`
     * were in the analyser's paths and in none of those lists.
     *
     * Blade templates are included for the same reason they are today: they are
     * `.php` by extension, they carry comments, and the rules that read them
     * already tell them apart by name.
     *
     * @return list<string>
     */
    public static function phpFiles(): array
    {
        $found = self::phpUnder(Tree::root());

        sort($found);

        return $found;
    }

    /**
     * The PSR-4 prefixes composer registered, by prefix.
     *
     * @return array<string, array<int, string>>
     */
    private static function registeredPrefixes(): array
    {
        /** @var array<string, array<int, string>> $registered */
        $registered = require Tree::at('vendor/composer/autoload_psr4.php');

        return $registered;
    }

    /**
     * Whether a directory is one of the measured source trees, or sits in one.
     *
     * Inside counts as well as equal to: `App\Providers\` is registered against
     * `bootstrap/Composition/NativePHP/Admitting`, which is a directory the
     * coverage floor measures by way of its parent. A comparison that asked
     * only for equality would drop it, and dropping it is how the one class the
     * vendor names for us stops being judged by anything.
     */
    private static function isASourceTree(string $directory): bool
    {
        $relative = self::relativeTo($directory);

        return array_any(
            self::expandedSourceTrees(),
            static fn(string $tree): bool => $relative === $tree || str_starts_with($relative, sprintf('%s/', $tree)),
        );
    }

    /**
     * Whether any of those directories is inside a measured tree and holds PHP.
     *
     * @param array<int, string> $directories
     */
    private static function pointsIntoAMeasuredTree(array $directories): bool
    {
        return array_any(
            $directories,
            static fn(string $directory): bool => self::isASourceTree($directory) && self::holdsPhp($directory),
        );
    }

    /**
     * The source trees with their globs resolved against what is on disk.
     *
     * @return list<string>
     */
    private static function expandedSourceTrees(): array
    {
        $found = [];

        foreach (self::sourceTrees() as $tree) {
            $found = [...$found, ...array_map(self::relativeTo(...), self::directoriesMatching($tree))];
        }

        return $found;
    }

    /**
     * The directories one of `phpunit.xml`'s patterns resolves to, absolute.
     *
     * @return list<string>
     */
    private static function directoriesMatching(string $pattern): array
    {
        $found = glob(Tree::at($pattern), GLOB_ONLYDIR);

        return $found === false ? [] : $found;
    }

    /** That path, as the repository writes it. */
    private static function relativeTo(string $path): string
    {
        $resolved = realpath($path);

        if (! is_string($resolved)) {
            return $path;
        }

        $root = realpath(Tree::root());

        return is_string($root) && str_starts_with($resolved, sprintf('%s/', $root))
            ? mb_substr($resolved, mb_strlen($root) + 1)
            : $resolved;
    }

    /** Whether a directory holds at least one PHP file, at any depth. */
    private static function holdsPhp(string $directory): bool
    {
        return Tree::filesUnder($directory, '.php') !== [];
    }

    /**
     * The paths one element of `phpunit.xml` declares.
     *
     * @return list<string>
     */
    private static function pathsAt(string $element): array
    {
        $configuration = simplexml_load_file(Tree::at('phpunit.xml'));

        if ($configuration === false) {
            throw new RuntimeException(
                'phpunit.xml could not be parsed, so nothing knows which trees this '
                . 'repository owns. A checker that finds nothing to check is the failure '
                . 'it exists to prevent.',
            );
        }

        $found = $configuration->xpath($element);

        if (! is_array($found) || $found === []) {
            throw new RuntimeException(sprintf(
                'phpunit.xml declares no `%s`. Every gate\'s scope is compared against '
                . 'that list, so an empty one would make every comparison hold.',
                $element,
            ));
        }

        return array_values(array_map(
            static fn(SimpleXMLElement $directory): string => trim((string) $directory),
            $found,
        ));
    }

    /**
     * The PHP under a directory, stopping at anything not ours.
     *
     * A walk with pruning rather than {@see Tree::filesUnder()}, because the
     * root holds `vendor` and reading a quarter of a gigabyte in order to throw
     * it away is the difference between a rule that runs and one somebody
     * switches off.
     *
     * @return list<string>
     */
    private static function phpUnder(string $directory): array
    {
        $found = self::phpFilesIn($directory);

        foreach (self::ourDirectoriesIn($directory) as $entry) {
            $found = [...$found, ...self::phpUnder($entry)];
        }

        return $found;
    }

    /**
     * The PHP files sitting directly in one directory.
     *
     * @return list<string>
     */
    private static function phpFilesIn(string $directory): array
    {
        $found = glob(sprintf('%s/*.php', $directory));

        return $found === false ? [] : $found;
    }

    /**
     * The directories inside one directory that hold PHP this repository wrote.
     *
     * @return list<string>
     */
    private static function ourDirectoriesIn(string $directory): array
    {
        $found = glob(sprintf('%s/*', $directory), GLOB_ONLYDIR);

        return array_values(array_filter(
            $found === false ? [] : $found,
            static fn(string $entry): bool => ! in_array(basename($entry), self::WHERE_A_WALK_STOPS, strict: true),
        ));
    }
}
