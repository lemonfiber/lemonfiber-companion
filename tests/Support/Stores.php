<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_unique;
use function array_values;
use function file_get_contents;
use function interface_exists;
use function is_string;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionClass;

use function sprintf;
use function str_starts_with;

/**
 * Where a capability keeps what the phone keeps, and what may reach it.
 *
 * A module that keeps something between launches keeps it inside itself: one
 * query class under `src/Internal/Store`, beside the module's own migrations
 * in `database/migrations`. That directory is what makes code a store, so the
 * walls around it are read from the path rather than from a list kept here — a
 * store written tomorrow is walled the moment its first class is.
 *
 * Only a capability holds a store. The same directory in any other kind of
 * module is not one, and the framework it names there is refused as it would
 * be anywhere else in that module.
 */
final readonly class Stores
{
    /**
     * Every capability holding a store.
     *
     * @return list<Module>
     */
    public static function all(): array
    {
        return array_values(array_filter(
            Module::all(),
            static fn(Module $module): bool => $module->kind === Kind::Capability
                && Tree::filesUnder(self::directoryOf($module), '.php') !== [],
        ));
    }

    /** The namespace a capability's store declares its classes in. */
    public static function namespaceOf(Module $module): string
    {
        return sprintf('%s\Internal\Store', $module->namespace);
    }

    /** Whether a file is part of a capability's store, where the framework may be named. */
    public static function holds(Module $module, string $file): bool
    {
        return $module->kind === Kind::Capability && str_starts_with($file, self::directoryOf($module));
    }

    /** Whether a file is one of a capability's own migrations, the one place its tables are created. */
    public static function isAMigrationOf(Module $module, string $file): bool
    {
        return $module->kind === Kind::Capability
            && str_starts_with($file, sprintf('%s/database/migrations/', $module->path));
    }

    /**
     * Every class a capability's store declares.
     *
     * @return list<class-string>
     */
    public static function classesOf(Module $module): array
    {
        return array_values(array_filter(
            $module->classNames(),
            static fn(string $name): bool => str_starts_with($name, sprintf('%s\\', self::namespaceOf($module))),
        ));
    }

    /**
     * Every port a store answers, which its owner declares.
     *
     * Only the owner's own interfaces: one a store implements from the kernel,
     * as every store implements what clears everything kept, is the kernel's
     * port and is counted there.
     *
     * @return list<class-string>
     */
    public static function ports(): array
    {
        $found = [];

        foreach (self::all() as $store) {
            foreach (self::classesOf($store) as $name) {
                $found = [...$found, ...self::portsItsOwnerDeclares($store, $name)];
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Every class name a file reaches for, resolved as PHP resolves it.
     *
     * Resolved rather than read off the imports, because a store is reached
     * from its own module: `Store\HealthReadingsInTheDatabase` written in
     * `Modules\Health\Internal` names the store without importing anything,
     * and a rule that read only the imports would pass it.
     *
     * @return list<string>
     */
    public static function namesIn(string $file): array
    {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            return [];
        }

        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($source);

        if ($statements === null) {
            return [];
        }

        $traverser = new NodeTraverser(new NameResolver());
        $resolved = $traverser->traverse($statements);

        $found = [];

        /** @var list<FullyQualified> $names */
        $names = new NodeFinder()->findInstanceOf($resolved, FullyQualified::class);

        foreach ($names as $name) {
            $found[] = $name->toString();
        }

        return $found;
    }

    /**
     * The interfaces one store class implements that its own module declares.
     *
     * @param class-string $name
     *
     * @return list<class-string>
     */
    private static function portsItsOwnerDeclares(Module $store, string $name): array
    {
        $found = [];

        foreach (new ReflectionClass($name)->getInterfaceNames() as $port) {
            if (interface_exists($port) && str_starts_with($port, sprintf('%s\\', $store->namespace))) {
                $found[] = $port;
            }
        }

        return $found;
    }

    private static function directoryOf(Module $module): string
    {
        return sprintf('%s/src/Internal/Store/', $module->path);
    }
}
