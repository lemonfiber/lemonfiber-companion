<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_keys;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function str_replace;
use function trim;

/**
 * What this application has agreed to install, read from every manifest.
 *
 * The root's and every module's, because a dependency added to a module ships
 * exactly as surely as one added to the root — and a module manifest is the
 * quieter of the two places to put one.
 */
final readonly class Manifests
{
    /**
     * Packages whose purpose is sending something to somebody other than the
     * operator.
     *
     * Named rather than matched on a word, because the word does not separate
     * them from the things this rule permits: `laravel/telescope` and
     * `laravel/pail` both look like observability and both stay on the device,
     * and a pattern built around "log" or "monitor" would refuse the tools that
     * make a fault legible while missing a reporter named for a colour.
     *
     * Adding a line here is how a new reporter gets refused, and the list being
     * written out is what makes that a deliberate act rather than a regex
     * somebody tunes until their package passes.
     */
    public const array REPORTS_TO_A_THIRD_PARTY = [
        'sentry/sentry', 'sentry/sentry-laravel',
        'bugsnag/bugsnag', 'bugsnag/bugsnag-laravel',
        'facade/ignition', 'spatie/laravel-flare', 'spatie/flare-client-php',
        'honeybadger-io/honeybadger-laravel',
        'rollbar/rollbar-laravel',
        'elastic/apm-agent-php',
        'datadog/dd-trace',
        'newrelic/monolog-enricher',
        'segment/analytics-php',
        'mixpanel/mixpanel-php',
        'google/analytics-data',
        'microsoft/application-insights',
    ];

    /**
     * Which of the named packages are required, and by which manifest.
     *
     * @param list<string> $refused
     * @return list<string> `package — manifest`, one per hit
     */
    public static function requiring(array $refused): array
    {
        $found = [];

        foreach (self::manifests() as $path) {
            foreach (self::packagesIn($path) as $package) {
                if (in_array($package, $refused, strict: true)) {
                    $found[] = sprintf('%s — %s', $package, self::asTheRepositorySeesIt($path));
                }
            }
        }

        return $found;
    }

    /**
     * Every package one manifest requires, in both sections.
     *
     * `require-dev` is read too: a tool there does not ship, but a crash
     * reporter there is a crash reporter pointed at a developer's machine, and
     * the rule is about the payload rather than about the deployment.
     *
     * @return list<string>
     */
    private static function packagesIn(string $path): array
    {
        $manifest = self::read($path);
        $found = [];

        foreach (['require', 'require-dev'] as $section) {
            $found = [...$found, ...self::namesIn($manifest[$section] ?? null)];
        }

        return $found;
    }

    /**
     * One manifest, decoded, or nothing where it cannot be read.
     *
     * @return array<mixed>
     */
    private static function read(string $path): array
    {
        $text = file_get_contents($path);

        if (! is_string($text)) {
            return [];
        }

        /** @var mixed $manifest */
        $manifest = json_decode($text, associative: true);

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * The package names in one `require` section.
     *
     * @return list<string>
     */
    private static function namesIn(mixed $section): array
    {
        if (! is_array($section)) {
            return [];
        }

        $found = [];

        foreach (array_keys($section) as $package) {
            if (is_string($package)) {
                $found[] = $package;
            }
        }

        return $found;
    }

    /** A manifest's path, as somebody reading the failure would write it. */
    private static function asTheRepositorySeesIt(string $path): string
    {
        return trim(str_replace(Tree::root(), '', $path), '/');
    }

    /**
     * The root manifest and every module's.
     *
     * @return list<string>
     */
    private static function manifests(): array
    {
        $found = [Tree::at('composer.json')];

        foreach (Module::all() as $module) {
            $found[] = sprintf('%s/composer.json', $module->path);
        }

        return $found;
    }
}
