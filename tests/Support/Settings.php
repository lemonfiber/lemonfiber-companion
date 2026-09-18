<?php

declare(strict_types=1);

namespace Tests\Support;

use function explode;
use function file_get_contents;
use function is_file;
use function is_string;
use function mb_strtolower;
use function preg_match;

use RuntimeException;

use function scandir;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * What this application can be configured with, read from the files that carry it.
 *
 * Environment files and `config/` both, because they are two halves of one
 * thing: a key in `.env.example` is a switch an operator is being shown, and a
 * key in `config/` is one the code reads. Either alone is a checker that passes
 * while the other file holds the setting.
 */
final readonly class Settings
{
    /** Words that only appear in a setting about certificate verification. */
    private const array ABOUT_VERIFICATION = [
        'verify_tls', 'verify_ssl', 'verify_peer', 'verify_host', 'verify_cert',
        'tls_verify', 'ssl_verify', 'insecure', 'allow_self_signed', 'skip_verify',
    ];

    /**
     * Every setting whose name is about certificate verification, and where.
     *
     * @return list<string> `KEY — path`, one per setting
     */
    public static function namingVerification(): array
    {
        $found = [];

        foreach (self::keys() as $key => $path) {
            foreach (self::ABOUT_VERIFICATION as $word) {
                if (str_contains($key, $word)) {
                    $found[] = sprintf('%s — %s', $key, $path);
                }
            }
        }

        return $found;
    }

    /**
     * Every settable key, lowercased, and the file it is in.
     *
     * Read as text rather than by loading the files. `config/` is PHP that
     * calls `env()`, and loading it would resolve the defaults away — the key
     * would be gone and its value would be whatever this machine happens to
     * have. It is the key that is being asked about.
     *
     * **Every environment file, not `.env.example` alone.** That file is the
     * one under review and therefore the one least likely to carry the setting;
     * `.env`, `.env.testing` and anything else beside them are builds this app
     * runs in, and every one of them is covered. A checker that read only the
     * reviewed file would pass while the setting sat in the file nobody reads,
     * which is the exact arrangement the requirement exists to refuse.
     *
     * @return array<string, string> key => path
     */
    private static function keys(): array
    {
        $found = [];
        $sources = [...Tree::filesUnder(Tree::at('config'), '.php'), ...self::environmentFiles()];

        // Refused rather than answered empty, the way `Coverage` refuses a
        // report that is not there. Every rule resting on this asks whether a
        // setting *exists*, so an empty read and a clean repository give the
        // same answer — and one of them is the rule going quiet about every
        // environment file at once.
        if ($sources === []) {
            throw new RuntimeException(sprintf(
                'No configuration or environment file was found under %s. These rules ask '
                . 'whether a setting exists, so reading nothing and finding nothing are the '
                . 'same answer here — and only one of them is true.',
                Tree::root(),
            ));
        }

        foreach ($sources as $path) {
            $source = file_get_contents($path);

            if (! is_string($source)) {
                continue;
            }

            $relative = trim(str_replace(Tree::root(), '', $path), '/');

            foreach (explode("\n", $source) as $line) {
                foreach (self::settingsIn($line) as $key) {
                    $found[$key] = $relative;
                }
            }
        }

        return $found;
    }

    /**
     * Every environment file in the repository root.
     *
     * Matched by prefix rather than named, so a `.env.staging` added next year
     * is read without anybody remembering to add it here.
     *
     * @return list<string>
     */
    private static function environmentFiles(): array
    {
        $found = [];

        $entries = scandir(Tree::root());

        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if (! str_starts_with($entry, '.env')) {
                continue;
            }

            $path = Tree::at($entry);

            if (is_file($path)) {
                $found[] = $path;
            }
        }

        return $found;
    }

    /**
     * The setting names one line declares.
     *
     * An `.env` line is `KEY=value`; a `config/` line names its key inside an
     * `env()` call or as an array key. Both are matched, because a setting read
     * with a default and never written to `.env.example` is still a setting.
     *
     * @return list<string>
     */
    private static function settingsIn(string $line): array
    {
        $said = trim($line);

        if ($said === '' || str_contains($said, '//') || str_contains($said, '#')) {
            return [];
        }

        $found = [];

        if (preg_match('/^([A-Z][A-Z0-9_]*)=/', $said, $matched) === 1) {
            $found[] = mb_strtolower($matched[1]);
        }

        if (preg_match("/env\\(\\s*'([^']+)'/", $said, $matched) === 1) {
            $found[] = mb_strtolower($matched[1]);
        }

        if (preg_match("/^'([a-z][a-z0-9_]*)'\\s*=>/", $said, $matched) === 1) {
            $found[] = mb_strtolower($matched[1]);
        }

        return $found;
    }
}
