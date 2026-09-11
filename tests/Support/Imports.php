<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function file_get_contents;
use function is_string;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

use function sprintf;
use function str_starts_with;

/**
 * Every name a file reaches for, read from the file itself.
 *
 * The boundary rules ask one question — does this module name something it may
 * not — and the answer has to be exact, because these are the rules the whole
 * architecture rests on. Pest's namespace expectations answer it by resolving a
 * string against the autoloader's registered PSR-4 prefixes, which means a
 * string that is not one of those prefixes matches no files and reports nothing.
 * `Native` is such a string: `Native\Mobile\` is registered and its parent is
 * not, so a rule written against `Native` is silent while a rule written against
 * `Native\Mobile` reports. Nothing distinguishes the two from the outside.
 *
 * Parsing the imports removes the autoloader from the question altogether. A
 * prefix is a prefix, a module that names a forbidden vendor is reported, and a
 * package that is not installed yet — `Lemonfiber\Sdk` today — is handled the
 * same way as one that is.
 */
final readonly class Imports
{
    /**
     * Every fully-qualified name the file names, imported or written out.
     *
     * @return list<string>
     */
    public static function of(string $file): array
    {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            return [];
        }

        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($source);

        if ($statements === null) {
            return [];
        }

        return [...self::imported($statements), ...self::writtenOut($statements)];
    }

    /**
     * Whether any of these names starts with the given namespace.
     *
     * Compared with a trailing separator so that `Native\Mobile` does not match
     * a hypothetical `Native\MobileSomething`, and the namespace itself is
     * accepted for the case where the name is the namespace.
     *
     * @param list<string> $names
     */
    public static function anyUnder(array $names, string $namespace): bool
    {
        return array_any($names, fn(string $name): bool => $name === $namespace || str_starts_with($name, sprintf('%s\\', $namespace)));
    }

    /**
     * @param array<Stmt> $statements
     *
     * @return list<string>
     */
    private static function imported(array $statements): array
    {
        $found = [];

        /** @var list<Use_> $uses */
        $uses = new NodeFinder()->findInstanceOf($statements, Use_::class);

        foreach ($uses as $use) {
            foreach ($use->uses as $single) {
                $found[] = $single->name->toString();
            }
        }

        /** @var list<GroupUse> $groups */
        $groups = new NodeFinder()->findInstanceOf($statements, GroupUse::class);

        foreach ($groups as $group) {
            foreach ($group->uses as $single) {
                $found[] = sprintf('%s\\%s', $group->prefix->toString(), $single->name->toString());
            }
        }

        return $found;
    }

    /**
     * Names written out at the point of use rather than imported.
     *
     * @param array<Stmt> $statements
     *
     * @return list<string>
     */
    private static function writtenOut(array $statements): array
    {
        $found = [];

        /** @var list<FullyQualified> $names */
        $names = new NodeFinder()->findInstanceOf($statements, FullyQualified::class);

        foreach ($names as $name) {
            $found[] = $name->toString();
        }

        return $found;
    }
}
