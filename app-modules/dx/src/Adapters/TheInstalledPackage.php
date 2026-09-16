<?php

declare(strict_types=1);

namespace Modules\Dx\Adapters;

use function array_map;
use function basename;

use Composer\InstalledVersions;

use function file_exists;
use function file_get_contents;
use function glob;
use function sprintf;

/**
 * The one file in this module that touches a filesystem.
 *
 * `B3` puts IO in an adapter and nowhere else, and this is that adapter. The
 * module's other classes read the installed SDK's source — its envelope
 * declarations, its endpoint declarations — and none of them opens a file:
 * they ask this, which is why `B3`'s permission is a single directory rather
 * than a module.
 *
 * Concentrating it here is worth more than rule compliance. What the readers
 * above do is parse; what this does is find and fetch. Those fail differently
 * and are fixed differently — a missing package is a composer problem and a
 * misread `array{…}` is a notation problem — and keeping them apart means a
 * failure names which of the two it is.
 *
 * **Where the package is, is asked rather than worked out.** The module is read
 * in two places that disagree about where the application root is: a suite runs
 * from the repository and a debug build runs from a bundle the packager
 * assembled. `InstalledVersions` is written by the autoloader either way and
 * answers with the version actually installed rather than the one somebody
 * expected. Reflection would answer too and `P4` refuses it, for a reason that
 * holds here: a member reached by name is a member no rule can find.
 */
final readonly class TheInstalledPackage
{
    /** The package whose contract this module stands in for. */
    private const string THE_SDK = 'lemonfiber/sdk-php';

    /**
     * Where the SDK sits, or nothing at all where it is not installed.
     */
    public static function whereItIs(): string
    {
        return InstalledVersions::getInstallPath(self::THE_SDK) ?? '';
    }

    /**
     * What one file inside the package says, or nothing where it is absent.
     *
     * A path this does not find answers with nothing rather than raising,
     * because every caller has a sentence for an empty reading and none has a
     * sentence for a raise arriving out of an expectation.
     *
     * Asked before it is read rather than read under `@`. Suppression is
     * refused here for `G11`'s reason: a warning raised under it is dropped
     * before the result sees it, so the run prints the warning and still exits
     * zero.
     */
    public static function text(string $relative): string
    {
        $path = sprintf('%s/%s', self::whereItIs(), $relative);

        if (! file_exists($path)) {
            return '';
        }

        $said = file_get_contents($path);

        return $said === false ? '' : $said;
    }

    /**
     * The files under one directory of the package whose names end a given way,
     * as names without the extension.
     *
     * Names rather than paths, because every caller wants the class and none
     * wants the location — handing back paths would put this adapter's
     * knowledge of where the package is into the callers, which is the thing it
     * exists to hold.
     *
     * In the order `glob` answers, which is alphabetical, so the order is
     * stable across machines without sorting it. That matters more than it
     * looks: `L6` is right that sorting text by byte order is wrong for
     * anything a person reads, and a sort here would be a sort of class names
     * nobody reads — so the honest answer is not a different comparator but no
     * sort at all.
     *
     * @return list<string>
     */
    public static function namesUnder(string $directory, string $ending): array
    {
        $found = glob(sprintf('%s/%s/*%s.php', self::whereItIs(), $directory, $ending));

        return array_map(
            static fn(string $path): string => basename($path, '.php'),
            $found === false ? [] : $found,
        );
    }
}
