<?php

declare(strict_types=1);

namespace Tests\Support;

use function class_exists;

use ReflectionClass;

use function str_contains;

/**
 * The components a screen can stand in its markup, and what each renders.
 *
 * The same two readings {@see Screens} does, over the other half of the view
 * layer. They are reused from there rather than copied, and neither is about a
 * screen: one reads a `render()` for the view it names and the other turns a
 * view name into a path. A second copy of either could disagree with the first,
 * and a disagreement between two readings is invisible from both sides.
 *
 * What is its own here is which classes count. A screen is marked by sitting
 * under `Internal\Screens`; a component by sitting under `View\Components`,
 * which is where `internachi/modular` looks for the class behind
 * `x-operator::something`.
 */
final readonly class Components
{
    /** Where a component's class sits in its module, which is what marks it as one. */
    private const string UNDER = '\\View\\Components\\';

    /**
     * Every component class in every module.
     *
     * @return list<ReflectionClass<object>>
     */
    public static function all(): array
    {
        $found = [];

        foreach (Module::all() as $module) {
            foreach ($module->classNames() as $name) {
                if (str_contains($name, self::UNDER) && class_exists($name)) {
                    $found[] = new ReflectionClass($name);
                }
            }
        }

        return $found;
    }

    /**
     * Every component, by the view it renders.
     *
     * @return array<string, ReflectionClass<object>>
     */
    public static function byTheViewTheyRender(): array
    {
        $found = [];

        foreach (self::all() as $component) {
            $view = Screens::theViewRenderedBy($component);

            if ($view !== '') {
                $found[$view] = $component;
            }
        }

        return $found;
    }
}
