<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function class_exists;
use function explode;
use function file_get_contents;

use Modules\Kernel\Api\Nonce;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\AStacksScreen;

use function preg_match;
use function preg_match_all;

use const PREG_SET_ORDER;

use ReflectionClass;
use ReflectionMethod;

use function sprintf;
use function str_contains;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * The screens this application has, and how each one is addressed.
 *
 * Four readings, each a pattern over source and each of them wanted by more
 * than one rule: which classes are screens, which template each renders, what
 * path each is asked for by, and which accessor hands one out. They sit
 * together because a second copy of any of them can disagree with the first,
 * and a disagreement between two readings is invisible from either side —
 * each rule is green about a tree the other one is not looking at.
 */
final readonly class Screens
{
    /** Where a screen's class sits in its module, which is what marks it as one. */
    private const string UNDER = '\\Screens\\';

    /**
     * Every screen class in every module.
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
     * Every screen, by the view it renders.
     *
     * @return array<string, ReflectionClass<object>>
     */
    public static function byTheViewTheyRender(): array
    {
        $found = [];

        foreach (self::all() as $screen) {
            $view = self::theViewRenderedBy($screen);

            if ($view !== '') {
                $found[$view] = $screen;
            }
        }

        return $found;
    }

    /**
     * Whether this screen declares a `render()` of its own.
     *
     * Asked apart from {@see self::theViewRenderedBy()} and answered from the
     * class rather than from the source, which is what makes a coverage rule
     * over this pairing able to fail: a screen whose view name cannot be read
     * still renders, and a rule that decided *renders* by *could read a name*
     * would drop that screen from both sides of its own comparison and report
     * a pass.
     *
     * @param ReflectionClass<object> $screen
     */
    public static function rendersAViewOfItsOwn(ReflectionClass $screen): bool
    {
        return array_any(
            $screen->getMethods(),
            static fn(ReflectionMethod $method): bool => $method->getName() === 'render'
                && $method->getDeclaringClass()->getName() === $screen->getName(),
        );
    }

    /**
     * The view this screen renders, or nothing where it renders none of its own.
     *
     * Read from the source rather than by calling `render()`, which would want
     * a constructed screen and therefore a container, a route and a stack — all
     * to learn a string written three lines from the method's signature.
     *
     * A view name may carry a directory segment and a capital in it, so both
     * are read: a pattern that could not spell one would answer nothing for
     * that screen, and nothing is what this says about a screen that renders no
     * view at all.
     *
     * @param ReflectionClass<object> $screen
     */
    public static function theViewRenderedBy(ReflectionClass $screen): string
    {
        if (! self::rendersAViewOfItsOwn($screen)) {
            return '';
        }

        $said = (string) file_get_contents((string) $screen->getFileName());

        return preg_match("/view\(\s*'([A-Za-z0-9:.\-]+)'/", $said, $named) === 1 ? $named[1] : '';
    }

    /**
     * Where a namespaced view name lives on disk.
     *
     * `operator::what-this-stack-runs` is the operator module's views directory
     * and the file of that name — the mapping Laravel makes at runtime, made
     * here without booting anything.
     */
    public static function theFileBehindTheView(string $named): string
    {
        [$module, $view] = explode('::', $named);

        return sprintf('app-modules/%s/resources/views/%s.blade.php', $module, str_replace('.', '/', $view));
    }

    /** A stack identifier shaped the way a real one is. */
    public static function aStackInTheUri(): string
    {
        return str_repeat('a', Nonce::SHORTEST);
    }

    /**
     * Every path the app can send anybody to, labelled by the case that hands it out.
     *
     * The two enums between them are all the screens there are — one names a
     * machine in its path and the other does not — so a screen missing from
     * here is a screen missing from an enum.
     *
     * @return array<string, string>
     */
    public static function everyPathAScreenHandsOut(): array
    {
        $paths = [];

        foreach (AScreenWithoutAStack::cases() as $screen) {
            $paths[sprintf('AScreenWithoutAStack::%s', $screen->name)] = $screen->value;
        }

        foreach (AStacksScreen::cases() as $screen) {
            // Asked which builder it needs rather than told, so a case added
            // with a second placeholder is covered here without anybody
            // remembering to add it — and a case that needs one and is asked
            // for the other refuses rather than handing back a path with
            // `{service}` still in it.
            $paths[sprintf('AStacksScreen::%s', $screen->name)] = $screen->alsoNeedsAService()
                ? $screen->forTheStacksService(self::aStackInTheUri(), 'gluetun')
                : $screen->forTheStack(self::aStackInTheUri());
        }

        return $paths;
    }

    /**
     * Which accessor hands out which screen, read off the source.
     *
     * The accessors are one line each and live either on the screen that offers
     * the road or on the type that knows where a machine's screens are, so
     * there is nothing to reflect over — what there is instead is a shape every
     * one of them has.
     *
     * The declaring class is carried with each, because an accessor name is
     * unique to the type that declares it and not to the application:
     * `onwardsTo()` means the sign-in screen on one and the machine's own
     * report on another.
     *
     * @return list<array{class: string, accessor: string, screen: string}>
     */
    public static function accessorsHandingOutAScreen(): array
    {
        $found = [];

        foreach (Module::all() as $module) {
            foreach ($module->classes() as $file) {
                foreach (self::handoutsIn($file) as $handout) {
                    $found[] = $handout;
                }
            }
        }

        return $found;
    }

    /**
     * Which accessor hands out which screen that needs no machine.
     *
     * @return array<string, string> the case's name, against the accessor's
     */
    public static function whatHandsOutAScreenWithoutAStack(): array
    {
        $named = 'AScreenWithoutAStack::';
        $found = [];

        foreach (self::accessorsHandingOutAScreen() as $handout) {
            if (str_starts_with($handout['screen'], $named)) {
                $found[substr($handout['screen'], strlen($named))] = $handout['accessor'];
            }
        }

        return $found;
    }

    /**
     * The accessors one file declares, and the screen each one answers with.
     *
     * @return list<array{class: string, accessor: string, screen: string}>
     */
    private static function handoutsIn(string $file): array
    {
        preg_match_all(
            '/public function (\w+)\([^)]*\): string\s*\{\s*return (AScreenWithoutAStack|AStacksScreen)::(\w+)->/',
            (string) file_get_contents($file),
            $accessors,
            PREG_SET_ORDER,
        );

        $declared = Imports::declaredName($file);
        $found = [];

        foreach ($accessors as [, $accessor, $enum, $case]) {
            $found[] = [
                'class' => $declared,
                'accessor' => $accessor,
                'screen' => sprintf('%s::%s', $enum, $case),
            ];
        }

        return $found;
    }
}
