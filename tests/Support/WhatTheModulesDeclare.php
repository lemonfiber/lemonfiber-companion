<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_diff;
use function array_merge;
use function array_values;
use function in_array;

use ReflectionClass;

/**
 * The types the modules declare as a port's own, read from the modules rather than from a list kept here.
 */
final readonly class WhatTheModulesDeclare
{
    /**
     * Every class or interface in a module that names this port itself, rather than through another type that does.
     *
     * @param class-string $port
     *
     * @return list<class-string>
     */
    public static function theirOwn(string $port): array
    {
        $declared = [];

        foreach (Module::all() as $module) {
            foreach ($module->classNames() as $name) {
                if (in_array($port, self::namedBy(new ReflectionClass($name)), strict: true)) {
                    $declared[] = $name;
                }
            }
        }

        return $declared;
    }

    /**
     * The interfaces a type names in its own declaration.
     *
     * @param ReflectionClass<object> $type
     *
     * @return list<string>
     */
    private static function namedBy(ReflectionClass $type): array
    {
        $inherited = [];
        $parent = $type->getParentClass();

        if ($parent !== false) {
            $inherited[] = $parent->getInterfaceNames();
        }

        foreach ($type->getInterfaces() as $interface) {
            $inherited[] = $interface->getInterfaceNames();
        }

        return array_values(array_diff($type->getInterfaceNames(), array_merge([], ...$inherited)));
    }
}
