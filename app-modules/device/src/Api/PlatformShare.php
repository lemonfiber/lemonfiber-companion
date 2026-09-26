<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Handover;
use Lemonfiber\Native\WhyNothingWasHandedOver;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;

/**
 * The platform's own share sheet, given a report to put in front of somebody.
 *
 * A report handed over rather than sent, made concrete: the app offers the
 * report and stops. Where it goes is a choice a person makes in an app this one
 * does not know about, which is the whole difference between this and the crash
 * reporter this app may not have.
 *
 * **Nothing is written to disk.** Both platforms' sheets will take a file, and
 * taking one would mean this application writing a diagnostic report into a
 * cache directory and leaving it there — nothing on this side ever learns when
 * the chosen app is done with it, so a file deleted at the right moment is a
 * file the chosen app cannot read, and one deleted at no moment is residue.
 * The report is text and travels as text.
 *
 * **The covering line is the report's own name rather than a sentence**, and
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
        return $this->offering($assembled->named(), $assembled->text());
    }

    public function passOn(AnInvitationToPassOn $invitation): Handed
    {
        return $this->offering($invitation->named(), $invitation->text());
    }

    /** The sheet, offered a title and a text, and what it answered in this application's terms. */
    private function offering(string $title, string $text): Handed
    {
        return $this->sheet->offer($title, $text)->either(
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
