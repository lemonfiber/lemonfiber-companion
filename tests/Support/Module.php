<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_map;
use function array_values;
use function class_exists;
use function dirname;
use function enum_exists;
use function explode;
use function file_get_contents;

use FilesystemIterator;

use function glob;
use function in_array;
use function interface_exists;
use function is_array;
use function is_dir;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function ucwords;

/**
 * One module, as its own manifest describes it.
 *
 * Read from disk rather than from a list kept here, for the same reason the
 * EDGE vocabulary is read from the parser: a second copy drifts, and drifts
 * quietly. A module whose manifest declares no kind is an error rather than a
 * module with no rules.
 */
final readonly class Module
{
    public function __construct(
        public string $name,
        public string $namespace,
        public Kind $kind,
        public string $path,
    ) {}

    /** @return list<self> */
    public static function all(): array
    {
        $root = dirname(__DIR__, 2);
        $manifests = glob(sprintf('%s/app-modules/*/composer.json', $root));

        if ($manifests === false) {
            throw new RuntimeException('app-modules could not be listed');
        }

        $found = [];

        foreach ($manifests as $manifest) {
            $found[] = self::read($manifest);
        }

        return $found;
    }

    /**
     * Modules holding at least one class.
     *
     * An empty module has nothing for an architecture rule to inspect, and Pest
     * raises rather than passing vacuously when asked about a namespace with no
     * classes in it. Filtering here keeps the suite honest as modules fill in:
     * the rules apply the moment there is something to apply them to.
     *
     * @return list<self>
     */
    public static function populated(): array
    {
        return array_values(array_filter(
            self::all(),
            static fn(self $module): bool => $module->classes() !== [],
        ));
    }

    /**
     * Every class file in the module, at any depth.
     *
     * `src/*.php` would see only the top level, and this layout deliberately
     * keeps classes one level down under `Api/` and `Internal/` — so a shallow
     * glob would report every module as empty and skip every rule.
     *
     * @return list<string>
     */
    public function classes(): array
    {
        return $this->phpFilesIn(sprintf('%s/src', $this->path));
    }

    /**
     * Every test file in the module, at any depth.
     *
     * Named by the convention rather than discovered by running them: H4 is
     * about where a file sits, and a test that fails to load is exactly the
     * case that must still be reported rather than skipped.
     *
     * @return list<string>
     */
    public function testFiles(): array
    {
        return array_values(array_filter(
            $this->phpFilesIn(sprintf('%s/tests', $this->path)),
            static fn(string $file): bool => str_ends_with($file, 'Test.php'),
        ));
    }

    /**
     * Every class this module declares, as a fully qualified name.
     *
     * Derived from the PSR-4 mapping rather than from an autoloader classmap,
     * which only exists when the autoloader was dumped optimized — a condition
     * that holds in CI and not always on a laptop. A rule that silently checks
     * nothing half the time is not a rule.
     *
     * @return list<class-string>
     */
    public function classNames(): array
    {
        $names = [];

        foreach ($this->classes() as $file) {
            $relative = str_replace([sprintf('%s/src/', $this->path), '.php'], '', $file);
            $name = sprintf('%s\\%s', $this->namespace, str_replace('/', '\\', $relative));

            if (class_exists($name) || interface_exists($name) || enum_exists($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Namespaces of every other module this one may not name.
     *
     * @return list<string>
     */
    public function forbiddenModuleNamespaces(): array
    {
        $permitted = array_map(
            static fn(Kind $kind): string => $kind->value,
            $this->kind->mayDependOn(),
        );

        $forbidden = [];

        foreach (self::all() as $other) {
            if ($other->name === $this->name) {
                continue;
            }

            if (! in_array($other->kind->value, $permitted, true)) {
                $forbidden[] = $other->namespace;

                continue;
            }

            // A permitted module is reachable only through its published Api.
            $forbidden[] = sprintf('%s\Internal', $other->namespace);
        }

        return $forbidden;
    }

    /** @return list<string> */
    private function phpFilesIn(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $found = [];

        $tree = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($tree as $file) {
            if ($file->getExtension() === 'php') {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }

    private static function read(string $manifest): self
    {
        $raw = file_get_contents($manifest);

        if (! is_string($raw)) {
            throw new RuntimeException(sprintf('%s could not be read', $manifest));
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('%s is not valid JSON', $manifest), 0, $e);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('%s does not describe a module', $manifest));
        }

        $name = $decoded['name'] ?? null;
        $extra = $decoded['extra'] ?? null;
        $lemonfiber = is_array($extra) ? $extra['lemonfiber'] ?? null : null;
        $kind = is_array($lemonfiber) ? $lemonfiber['kind'] ?? null : null;

        if (! is_string($name) || ! str_contains($name, '/')) {
            throw new RuntimeException(sprintf('%s declares no module name', $manifest));
        }

        if (! is_string($kind)) {
            throw new RuntimeException(sprintf(
                '%s declares no kind. Every module states what it is in extra.lemonfiber.kind, '
                . 'because that is what generates its boundary rules',
                $manifest,
            ));
        }

        $short = explode('/', $name)[1];

        return new self(
            name: $short,
            namespace: sprintf('Modules\\%s', str_replace(' ', '', ucwords(str_replace('-', ' ', $short)))),
            kind: Kind::from($kind),
            path: dirname($manifest),
        );
    }
}
