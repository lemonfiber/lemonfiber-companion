<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\OneHome;

use function array_key_exists;
use function array_keys;
use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Which declarations can refer to each other: two in one module, or in modules one of which requires the other.
 *
 * The bridge is a package of its own that requires no module and that no
 * module's manifest requires. The composition root and the suites' support
 * can refer to everything.
 */
final readonly class Reach
{
    /** Where a module, or the bridge, is in a path, and what it is called there. */
    private const string WHERE_IT_IS = '#/(?:app-modules/([^/]+)|(bridge))/src/#';

    /** What a module's manifest names another module by, before the module's own name. */
    private const string A_MODULE = 'modules/';

    /** @param array<string, list<string>> $requires each module, by its directory, and the modules it requires */
    private function __construct(private array $requires) {}

    /** The reach every module's manifest declares, read from the tree beside this rule. */
    public static function declared(): self
    {
        $requires = [];

        foreach (glob(sprintf('%s/app-modules/*/composer.json', dirname(__DIR__, 3))) ?: [] as $manifest) {
            $requires[basename(dirname($manifest))] = self::modulesRequiredBy($manifest);
        }

        return new self($requires);
    }

    /** Whether a declaration in one file and a declaration in another can refer to each other. */
    public function joins(string $one, string $other): bool
    {
        $first = self::moduleOf($one);
        $second = self::moduleOf($other);

        return $first === '' || $second === '' || $first === $second || $this->requires($first, $second) || $this->requires($second, $first);
    }

    private function requires(string $module, string $required): bool
    {
        return array_key_exists($module, $this->requires) && in_array($required, $this->requires[$module], strict: true);
    }

    /** The module a file is in, by its directory, the bridge, or nothing where it can refer to everything. */
    private static function moduleOf(string $file): string
    {
        if (preg_match(self::WHERE_IT_IS, $file, $found) !== 1) {
            return '';
        }

        return $found[1] === '' ? $found[2] : $found[1];
    }

    /** @return list<string> */
    private static function modulesRequiredBy(string $manifest): array
    {
        $read = file_get_contents($manifest);
        $declared = is_string($read) ? json_decode($read, associative: true) : null;
        $required = is_array($declared) && is_array($declared['require'] ?? null) ? $declared['require'] : [];
        $modules = [];

        foreach (array_keys($required) as $name) {
            if (is_string($name) && str_starts_with($name, self::A_MODULE)) {
                $modules[] = substr($name, strlen(self::A_MODULE));
            }
        }

        return $modules;
    }
}
