<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function file_get_contents;
use function is_string;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
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
     * The fully-qualified name the file declares, or an empty string.
     *
     * Read from the file rather than derived from its path, because the two
     * disagree exactly when W2 is being broken — and a name derived from the
     * path sends the autoloader to a file that declares something else, which
     * loads it once without defining the expected class and leaves it to be
     * loaded again by whatever looks next. The second load is a fatal
     * redeclaration, and it happens before any rule can report the misplacement.
     */
    public static function declaredName(string $file): string
    {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            return '';
        }

        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($source);

        if ($statements === null) {
            return '';
        }

        $finder = new NodeFinder();

        /** @var ClassLike|null $declaration */
        $declaration = $finder->findFirstInstanceOf($statements, ClassLike::class);

        if ($declaration?->name === null) {
            return '';
        }

        /** @var Namespace_|null $namespace */
        $namespace = $finder->findFirstInstanceOf($statements, Namespace_::class);

        // Joined by hand rather than read from `namespacedName`, which the
        // parser only fills in once a NameResolver has walked the tree.
        return $namespace?->name === null
            ? $declaration->name->toString()
            : sprintf('%s\\%s', $namespace->name->toString(), $declaration->name->toString());
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
