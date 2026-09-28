<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Handover;
use Lemonfiber\Native\Offered;
use Lemonfiber\Native\WhyNothingWasHandedOver;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;

/**
 * The platform's own share sheet, given a report, an invitation or a bundle to put in front of somebody.
 *
 * Handed over rather than sent, made concrete: the app offers it and stops.
 * Where it goes is a choice a person makes in an app this one does not know
 * about, which is the whole difference between this and the crash reporter
 * this app may not have.
 *
 * **Text travels as text, and a bundle as one swept file.** A report and an
 * invitation are handed to the sheet as text, and nothing is written. A
 * support bundle is an archive: the bridge writes it into one app-private
 * directory used for nothing else, grants the chosen app read access to that
 * one file, and empties the directory before every new handover and on the next
 * launch. At most one bundle is ever on the device, and only until one of those
 * comes round.
 *
 * **The covering line is the thing's own name rather than a sentence**, and
 * deliberately: whatever the operator picks will put this where somebody reads
 * it, and a sentence this app wrote about somebody else's fault is a sentence
 * that is wrong as often as it is right.
 *
 * This adapter keeps no branch of its own beyond naming the two refusals in the
 * terms the application reasons in. Whether the sheet opened is the platform's
 * answer, read where it is tested.
 */
final readonly class PlatformShare implements Sharing
{
    public function __construct(private Handover $sheet) {}

    public function hand(Assembled $assembled): Handed
    {
        return $this->handed($this->sheet->offer($assembled->named(), $assembled->text()));
    }

    public function passOn(AnInvitationToPassOn $invitation): Handed
    {
        return $this->handed($this->sheet->offer($invitation->named(), $invitation->text()));
    }

    public function handOver(ABundleFile $bundle): Handed
    {
        return $this->handed($this->sheet->offerFile($bundle->named(), $bundle->named(), $bundle->bytes()));
    }

    /** What the sheet answered, in this application's terms. */
    private function handed(Offered $offered): Handed
    {
        return $offered->either(
            offered: static fn(): Handed => Handed::over(),
            refused: static fn(WhyNothingWasHandedOver $why): Handed => Handed::refused(self::meaning($why)),
        );
    }

    /** What one of the sheet's refusals means in the terms this application reasons in. */
    private static function meaning(WhyNothingWasHandedOver $why): WhyNothingWasShared
    {
        return match ($why) {
            WhyNothingWasHandedOver::NothingToHandOver => WhyNothingWasShared::NothingToHandOver,
            WhyNothingWasHandedOver::ThePlatformWouldNot => WhyNothingWasShared::TheDeviceWouldNotOffer,
        };
    }
}
