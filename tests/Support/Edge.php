<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

use function is_array;
use function is_string;

use Native\Mobile\Edge\ComponentRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\TailwindParser;
use ReflectionClass;
use RuntimeException;

use function sort;
use function sprintf;
use function str_replace;

/**
 * What EDGE actually accepts, asked of EDGE.
 *
 * Every answer here comes out of the installed package rather than out of a
 * list kept beside it. A transcribed vocabulary is a second source of truth
 * that goes stale on the next NativePHP release and goes stale silently — it
 * keeps passing, which is the shape of every dead rule this suite has already
 * had to find. Asking the package means a release that adds a utility makes it
 * available here on the same day, and a release that removes one starts
 * failing on the same day.
 */
final readonly class Edge
{
    /**
     * Classes the parser did not recognise, out of the strings given.
     *
     * This is the parser's own diagnostic channel — the one the framework uses
     * to tell a developer what it dropped — rather than a re-implementation of
     * its grammar. It already knows that a platform variant aimed at the other
     * platform is a deliberate no-op rather than a mistake, which a
     * reimplementation would get wrong.
     *
     * `$scope` must be unique per call. The parser remembers which classes it
     * has already reported for a given view name and stays silent the second
     * time, and `clearCache()` does not reset that — so a scope reused within
     * one process returns nothing and the check passes having looked at
     * nothing.
     *
     * @param list<string> $classStrings
     *
     * @return list<string>
     */
    public static function unsupportedClasses(string $scope, array $classStrings): array
    {
        // Read when the scope opens, not when a class is parsed, so it has to
        // be true before the first call rather than merely before the last.
        Config::set('app.debug', value: true);

        $dropped = [];

        Event::listen(MessageLogged::class, static function (MessageLogged $message) use (&$dropped): void {
            $dropped = [...$dropped, ...self::classesIn($message)];
        });

        TailwindParser::clearCache();
        TailwindParser::beginViewDiagnostics($scope);

        foreach ($classStrings as $classString) {
            TailwindParser::parse($classString);
        }

        TailwindParser::endViewDiagnostics();

        sort($dropped);

        return array_values(array_unique($dropped));
    }

    /**
     * Whether a value in a class position names a literal colour.
     *
     * The parser's own colour grammar, so palette names, hex in three shapes
     * and opacity suffixes are all recognised without a palette being copied
     * here. A theme token answers null, which is exactly the distinction a
     * literal colour is refused on.
     */
    public static function isLiteralColour(string $value): bool
    {
        return TailwindParser::resolveColorValue($value) !== null;
    }

    /**
     * Every tag name a template may use, in kebab form.
     *
     * Three sources, because the renderer consults three: the types the
     * collector handles in its own match arms, the elements registered by the
     * service provider, and the child components an application registers.
     * A tag in none of them throws `Unknown native element type` when the
     * screen is opened.
     *
     * @return list<string>
     */
    public static function knownTags(): array
    {
        $types = array_merge(
            self::collectorBuiltins(),
            array_keys(ElementRegistry::all()),
            array_keys(ComponentRegistry::all()),
        );

        $tags = array_map(static fn(string $type): string => str_replace('_', '-', $type), $types);

        sort($tags);

        return array_values(array_unique($tags));
    }

    /**
     * The class names carried by one diagnostic log record.
     *
     * @return list<string>
     */
    private static function classesIn(MessageLogged $message): array
    {
        $classes = $message->context['classes'] ?? null;

        if (! is_array($classes)) {
            return [];
        }

        $found = [];

        foreach ($classes as $class) {
            if (is_string($class)) {
                $found[] = $class;
            }
        }

        return $found;
    }

    /**
     * The types the collector renders without consulting a registry.
     *
     * Read off the class rather than copied out of it. The constant is not
     * public, so this is the one place the suite reaches past a package's
     * visibility — and it is worth it: the alternative is a transcription that
     * a NativePHP release can contradict without anything here noticing. If
     * the constant is ever renamed this raises immediately, which is the
     * failure mode to want.
     *
     * @return list<string>
     */
    private static function collectorBuiltins(): array
    {
        $constants = new ReflectionClass(NativeElementCollector::class)->getConstants();
        $builtins = $constants['COLLECTOR_BUILTIN_TYPES'] ?? null;

        if (! is_array($builtins) || $builtins === []) {
            throw new RuntimeException(sprintf(
                '%s::COLLECTOR_BUILTIN_TYPES is gone or empty, so the tags the collector '
                . 'renders itself can no longer be read from the package. Find where they '
                . 'live now — do not write the list out here, which is the transcription '
                . 'this class exists to avoid.',
                NativeElementCollector::class,
            ));
        }

        $found = [];

        foreach ($builtins as $type) {
            if (is_string($type)) {
                $found[] = $type;
            }
        }

        return $found;
    }
}
