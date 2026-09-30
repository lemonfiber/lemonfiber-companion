<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

use function sodium_bin2base64;

/**
 * The platform's own share sheet, given something to put in front of somebody.
 *
 * The PHP face of lemonfiber's own handover. Two calls: here is a title and some
 * text, offer it; or here is a title and a file, offer that. Where either goes
 * is a choice a person makes in an app this one does not know about, which is
 * the whole difference between this and the crash reporter this application may
 * not have.
 *
 * **Text is handed over as text, and a file is written once and swept.** Text
 * goes straight into whatever the operator picks. A file is written by the
 * device into one app-private directory used for nothing else, and handed to
 * the sheet with a grant to read that one file. Nothing on this side learns
 * when the chosen app is done with it, so the directory is emptied before every
 * new handover and on the next launch: at most one file is ever in it.
 *
 * **There is deliberately no answer for what they chose.** Reporting which app
 * received a diagnostic report would be this application learning something
 * about the operator it has no reason to know, and *must not be transmitted by
 * the app* is not honoured by an app that watches where it went.
 *
 * **Off a handset it reports the least it can.** The sheet was not presented,
 * because there is no sheet. A stand-in claiming otherwise would make a test
 * about offering a report pass on a machine with nothing to offer it to.
 */
final readonly class Handover
{
    /** The word the bridge answers where the sheet was presented. */
    private const string OFFERED = 'offered';

    /**
     * Offer it to whoever the operator picks.
     *
     * The title travels beside the text because the sheet shows one, and
     * whatever the operator picks will put it where somebody reads it — so it
     * is the report's own name rather than a sentence this application wrote
     * about somebody else's fault.
     */
    public function offer(string $title, string $text): Offered
    {
        return $this->offered(Call::Offer, ['title' => $title, 'text' => $text]);
    }

    /**
     * Write the file into the share cache and offer it to whoever the operator picks.
     *
     * The name is what the file is called where it lands, and is one file's
     * name: the device refuses a name that is nothing or a path, and a file of
     * no bytes, as nothing to hand over. The bytes travel as base64 with its
     * padding, because the bridge carries JSON and an archive is not text, and
     * that is the variant both platforms' decoders read.
     */
    public function offerFile(string $title, string $name, string $bytes): Offered
    {
        return $this->offered(Call::OfferFile, [
            'title' => $title,
            'name' => $name,
            'bytes' => sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_ORIGINAL),
        ]);
    }

    /**
     * One handover asked for, and what came of it.
     *
     * @param array<string, string> $with
     */
    private function offered(Call $call, array $with): Offered
    {
        $said = WhatTheBridgeAnswered::to($call, $with);

        if ($said->outcome() === self::OFFERED) {
            return Offered::toThem();
        }

        return Offered::refused($this->why($said));
    }

    /**
     * Which refusal it was, or the recoverable one where it did not say.
     *
     * A word this build does not know, a malformed envelope and no bridge at
     * all all land on *the platform would not*, which is the refusal a caller
     * can act on: it says try again, where the other says the report was never
     * assembled and trying again will do nothing.
     */
    private function why(WhatTheBridgeAnswered $said): WhyNothingWasHandedOver
    {
        return WhyNothingWasHandedOver::tryFrom((string) $said->word(WhatAnAnswerHolds::Because))
            ?? WhyNothingWasHandedOver::ThePlatformWouldNot;
    }
}
