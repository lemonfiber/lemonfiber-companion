<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_slice;
use function array_unique;
use function array_values;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_string;

use Native\Mobile\Edge\NativeRouter;

use function preg_match_all;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function sprintf;

/**
 * The navigation an operator can actually perform, as a graph.
 *
 * A screen's ways off it are written as `@navigate="{{ $this->goes()->health() }}"`
 * — an accessor call rather than a path — so following one means resolving the
 * accessor to a screen the way the device will at render time. Three readings
 * do that, and every one of them is {@see Screens}': which accessor answers
 * with which case, which path each case hands out, and which screen the router
 * serves under it.
 *
 * What this cannot resolve it reports, and a rule built on it has to say so.
 * A silently dropped edge makes a reachable screen look stranded, or — worse,
 * because it is the quiet direction — makes a stranded one look reachable.
 */
final readonly class WhereAScreenCanSendYou
{
    /**
     * The path the device asks this application for when it launches.
     *
     * The opening screen is not named here: it is whichever screen the router
     * is serving under this path, read off the same registry every button is
     * checked against. Naming the screen would leave this rule quietly right
     * about the wrong screen on the day the app opens on another one.
     *
     * The path itself is the one thing that cannot be derived from anything in
     * this repository. `Route::native()` registers an ordinary GET route beside
     * the native one because the device drives this application through the
     * HTTP kernel, and the web view it drives it from loads the application's
     * root. Nothing here decides that, so nothing here can be read for it.
     */
    private const string AT_LAUNCH = '/';

    /**
     * @param array<string, string> $throughAType accessor => the case it answers with
     * @param array<string, string> $fromAScreen "class::accessor" => the case it answers with
     * @param array<string, string> $under case => the screen class the router serves
     */
    private function __construct(
        private array $throughAType,
        private array $fromAScreen,
        private array $under,
    ) {}

    /** The three readings, taken once. */
    public static function read(): self
    {
        return new self(
            self::handoutsAwayFromAScreen(),
            self::handoutsOnAScreen(),
            self::screenUnderEachCase(),
        );
    }

    /** The screen a launch lands on, or nothing where the router serves none. */
    public function atLaunch(): string
    {
        return self::theClassServing(self::AT_LAUNCH);
    }

    /**
     * Which screen the router serves under each case's path.
     *
     * @return array<string, string>
     */
    public function screensTheRouterServes(): array
    {
        return $this->under;
    }

    /**
     * Every navigation an operator can make, and every one that could not be read.
     *
     * @return array{steps: list<array{from: string, at: string, to: string}>, unreadable: list<string>}
     */
    public function everyNavigation(): array
    {
        $steps = [];
        $unreadable = [];

        foreach (Screens::byTheViewTheyRender() as $view => $screen) {
            foreach ($this->waysOffThe($view) as $expression) {
                $reached = $this->casesReachedBy($screen, $expression, []);

                $unreadable = [...$unreadable, ...$this->whatIsNoStep($view, $expression, $reached)];
                $steps = [...$steps, ...$this->stepsOf($screen, $view, $reached)];
            }
        }

        return ['steps' => $steps, 'unreadable' => $unreadable];
    }

    /**
     * Which screens a person can get to from one of them, following every step.
     *
     * @param list<array{from: string, at: string, to: string}> $steps
     * @return list<string>
     */
    public function reachedFrom(string $opening, array $steps): array
    {
        $reached = [$opening];
        $frontier = [$opening];

        while ($frontier !== []) {
            $next = $this->notYet($this->onwardsFrom(array_pop($frontier), $steps), $reached);

            $reached = [...$reached, ...$next];
            $frontier = [...$frontier, ...$next];
        }

        return $reached;
    }

    /**
     * The ones not already arrived at, each counted once.
     *
     * Kept apart from the walk so that a screen two others both point at is
     * added on the first of them and never queued twice: a frontier holding
     * the same screen twice is a walk that reads the whole graph again for it.
     *
     * @param list<string> $these
     * @param list<string> $arrivedAt
     * @return list<string>
     */
    private function notYet(array $these, array $arrivedAt): array
    {
        return array_values(array_unique(array_filter(
            $these,
            static fn(string $one): bool => ! in_array($one, $arrivedAt, strict: true),
        )));
    }

    /**
     * The accessors declared somewhere other than on a screen.
     *
     * Keyed by the accessor alone, because that is how a template names one:
     * `$this->goes()->health()` says which accessor and never which type. The
     * type is the one that knows where a machine's screens are, found by not
     * being a screen itself rather than by name.
     *
     * @return array<string, string>
     */
    private static function handoutsAwayFromAScreen(): array
    {
        $screens = self::screenClassNames();
        $found = [];

        foreach (Screens::accessorsHandingOutAScreen() as $handout) {
            if (! in_array($handout['class'], $screens, strict: true)) {
                $found[$handout['accessor']] = $handout['screen'];
            }
        }

        return $found;
    }

    /**
     * The accessors a screen declares itself.
     *
     * Keyed by the class as well, because an accessor name is unique to the
     * type declaring it: three screens declare `onwardsTo()` and they do not
     * all mean the same screen.
     *
     * @return array<string, string>
     */
    private static function handoutsOnAScreen(): array
    {
        $screens = self::screenClassNames();
        $found = [];

        foreach (Screens::accessorsHandingOutAScreen() as $handout) {
            if (in_array($handout['class'], $screens, strict: true)) {
                $found[sprintf('%s::%s', $handout['class'], $handout['accessor'])] = $handout['screen'];
            }
        }

        return $found;
    }

    /** @return list<string> */
    private static function screenClassNames(): array
    {
        return array_map(
            static fn(ReflectionClass $screen): string => $screen->getName(),
            Screens::all(),
        );
    }

    /**
     * Which screen the router serves under the path each case hands out.
     *
     * @return array<string, string>
     */
    private static function screenUnderEachCase(): array
    {
        $found = [];

        foreach (Screens::everyPathAScreenHandsOut() as $case => $path) {
            $class = self::theClassServing($path);

            if ($class !== '') {
                $found[$case] = $class;
            }
        }

        return $found;
    }

    private static function theClassServing(string $path): string
    {
        $resolved = NativeRouter::resolve($path);
        $class = is_array($resolved) ? $resolved['class'] ?? null : null;

        return is_string($class) ? $class : '';
    }

    /**
     * The expression behind every way off one screen.
     *
     * @return list<string>
     */
    private function waysOffThe(string $view): array
    {
        $path = Tree::at(Screens::theFileBehindTheView($view));

        if (! is_file($path)) {
            return [];
        }

        preg_match_all('/@navigate\s*=\s*(["\'])(.*?)\1/s', (string) file_get_contents($path), $found);

        return $found[2];
    }

    /**
     * Which screens one expression reaches, or nothing where it reaches none.
     *
     * @param ReflectionClass<object> $screen
     * @param list<string> $seen
     * @return list<string>
     */
    private function casesReachedBy(ReflectionClass $screen, string $php, array $seen): array
    {
        return array_values(array_unique([
            ...$this->throughATypeIn($php),
            ...$this->throughTheScreensOwn($screen, $php, $seen),
        ]));
    }

    /**
     * The cases reached by calling an accessor on the type that knows the routes.
     *
     * @return list<string>
     */
    private function throughATypeIn(string $php): array
    {
        preg_match_all('/->(\w+)\s*\(/', $php, $called);

        return array_values(array_filter(
            array_map(fn(string $name): string => $this->throughAType[$name] ?? '', $called[1]),
            static fn(string $case): bool => $case !== '',
        ));
    }

    /**
     * The cases reached by asking this screen for one of its own accessors.
     *
     * @param ReflectionClass<object> $screen
     * @param list<string> $seen
     * @return list<string>
     */
    private function throughTheScreensOwn(ReflectionClass $screen, string $php, array $seen): array
    {
        preg_match_all('/\$this->(\w+)\s*\(/', $php, $asked);

        $found = [];

        foreach ($asked[1] as $accessor) {
            $found = [...$found, ...$this->casesBehind($screen, $accessor, $seen)];
        }

        return $found;
    }

    /**
     * What one accessor on one screen answers with, following it where it delegates.
     *
     * An accessor that answers with a case directly is that case. One that does
     * not is followed into its own body, which is where `tappingGoesTo()` has
     * its branch: two destinations depending on whether the device still holds
     * a session, and an operator can be on either.
     *
     * @param ReflectionClass<object> $screen
     * @param list<string> $seen
     * @return list<string>
     */
    private function casesBehind(ReflectionClass $screen, string $accessor, array $seen): array
    {
        $key = sprintf('%s::%s', $screen->getName(), $accessor);

        if (array_key_exists($key, $this->fromAScreen)) {
            return [$this->fromAScreen[$key]];
        }

        $body = in_array($key, $seen, strict: true) ? '' : $this->bodyOf($screen, $accessor);

        return $body === '' ? [] : $this->casesReachedBy($screen, $body, [...$seen, $key]);
    }

    /**
     * The source of an accessor that answers with a path, or nothing.
     *
     * Only the ones answering with a string are followed. A path is a string,
     * so a method answering with anything else cannot be one — and following
     * every call a template makes would drag in whatever a screen asks itself
     * on the way to drawing a row.
     *
     * @param ReflectionClass<object> $screen
     */
    private function bodyOf(ReflectionClass $screen, string $name): string
    {
        if (! $screen->hasMethod($name)) {
            return '';
        }

        $answers = $screen->getMethod($name)->getReturnType();

        if (! $answers instanceof ReflectionNamedType || $answers->getName() !== 'string') {
            return '';
        }

        return $this->sourceOf($screen->getMethod($name));
    }

    private function sourceOf(ReflectionMethod $method): string
    {
        $said = file_get_contents((string) $method->getFileName());
        $opens = $method->getStartLine();
        $closes = $method->getEndLine();

        if (! is_string($said) || $opens === false || $closes === false) {
            return '';
        }

        return implode("\n", array_slice(explode("\n", $said), $opens - 1, $closes - $opens + 1));
    }

    /**
     * What one `@navigate` could not be turned into a step, and why.
     *
     * @param list<string> $reached
     * @return list<string>
     */
    private function whatIsNoStep(string $view, string $expression, array $reached): array
    {
        if ($reached === []) {
            return [sprintf('%s — @navigate="%s" names no screen this rule can follow', $view, $expression)];
        }

        return array_map(
            static fn(string $case): string => sprintf(
                '%s — @navigate="%s" reaches %s, which the router serves nothing under',
                $view,
                $expression,
                $case,
            ),
            array_values(array_filter(
                $reached,
                fn(string $case): bool => ! array_key_exists($case, $this->under),
            )),
        );
    }

    /**
     * The steps one `@navigate` is, one per screen it can land on.
     *
     * @param ReflectionClass<object> $screen
     * @param list<string> $reached
     * @return list<array{from: string, at: string, to: string}>
     */
    private function stepsOf(ReflectionClass $screen, string $view, array $reached): array
    {
        $steps = [];

        foreach ($reached as $case) {
            $to = $this->under[$case] ?? '';

            if ($to !== '') {
                $steps[] = ['from' => $screen->getName(), 'at' => $view, 'to' => $to];
            }
        }

        return $steps;
    }

    /**
     * @param list<array{from: string, at: string, to: string}> $steps
     * @return list<string>
     */
    private function onwardsFrom(string $here, array $steps): array
    {
        return array_values(array_map(
            static fn(array $step): string => $step['to'],
            array_filter($steps, static fn(array $step): bool => $step['from'] === $here),
        ));
    }
}
