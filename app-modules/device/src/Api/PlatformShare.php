<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Closure;

use function file_put_contents;
use function is_writable;

use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;
use Native\Mobile\Share as Platform;

use function sprintf;
use function sys_get_temp_dir;

/**
 * The platform's own share sheet, given a report to put in front of somebody.
 *
 * `N4-R13`'s second clause made concrete: the app writes the report where the
 * platform can read it, asks for the sheet, and stops. Where it goes is a
 * choice a person makes in an app this one does not know about, which is the
 * whole difference between this and the crash reporter `N4-R12` refuses.
 *
 * **The file is written because the platform takes a path**, not because this
 * application wanted a file. `Share::file()` is the only call that carries text
 * a person can read afterwards — `url()` puts it in a link, which is a report
 * in somebody's browser history — so a temporary file is what the sheet costs.
 *
 * **It goes in the system temporary directory and is not cleaned up here.** A
 * file deleted the moment the sheet opens is a file the chosen app cannot read,
 * and this side never learns when that app is done; the platform empties the
 * directory instead, which is what the directory is for. The report holds no
 * credential, no address and no reading from a stack — {@see \Modules\Kernel\Api\Diagnostics}
 * is what makes that true, and it is why leaving one there is not a leak.
 *
 * **The filename is the one the report gave itself**, fixed rather than
 * timestamped: a support thread with four attachments and no idea which is
 * current is what a timestamped name produces, and rewriting the same path is
 * the behaviour that avoids it.
 */
final readonly class PlatformShare implements Sharing
{
    /**
     * @param Closure(string, string, string): void $offer the sheet, given a
     *        title, a covering line and the path to the file
     *
     * @param-later-invoked-callable $offer The platform's own method, handed in
     * rather than called for `G1`'s reason: the sheet cannot be opened on a
     * laptop, so a test drives this through a closure it wrote and the adapter
     * is exercised rather than skipped.
     */
    public function __construct(private Closure $offer, private string $into) {}

    /** The adapter as the composition root builds it, over the real sheet. */
    public static function onTheDevice(Platform $sheet): self
    {
        return new self($sheet->file(...), sys_get_temp_dir());
    }

    public function hand(Assembled $assembled): Handed
    {
        $path = sprintf('%s/%s', $this->into, $assembled->named());

        // One condition rather than a guard and a second check of the same
        // thing. The short circuit is load-bearing: `C4` forbids `@`, so the
        // write must not be attempted where it would warn — and `is_writable`
        // answers false for a path that is not there and for one that is not a
        // directory, so it is the whole guard rather than half of one.
        //
        // The write's own result joins the same condition instead of getting a
        // branch of its own, because the only way it fails after that check is
        // a disk filling between the two calls. That is real and it is not a
        // different answer: there was nowhere to write it either way.
        $written = is_writable($this->into)
            && file_put_contents($path, $assembled->text()) !== false;

        if (! $written) {
            return Handed::refused(WhyNothingWasShared::NowhereToWriteIt);
        }

        // The covering line is the report's own name rather than a sentence,
        // and deliberately: whatever the operator picks will put this where
        // somebody reads it alongside the file, and a sentence this app wrote
        // about somebody else's fault is a sentence that is wrong as often as
        // it is right.
        ($this->offer)($assembled->named(), $assembled->named(), $path);

        return Handed::over();
    }
}
