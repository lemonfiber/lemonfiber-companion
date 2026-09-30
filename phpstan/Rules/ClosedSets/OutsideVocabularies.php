<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\ClosedSets;

/**
 * D8 — the files whose closed set of strings is somebody else's, each with
 * whose it is.
 *
 * A set the outside world declares is read in its own words where it arrives,
 * and nowhere past that. The list only shrinks: `TheExemptionsOnlyShrinkTest`
 * holds it to a ceiling and refuses a file that is not there.
 */
final readonly class OutsideVocabularies
{
    /** @var array<string, string> by file, relative to the repository, whose words they are */
    public const array IN = [
        'app-modules/kernel/src/Api/AWrittenBundle.php' => "the filesystem's words for a last segment that names no file",
    ];
}
