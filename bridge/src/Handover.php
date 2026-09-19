<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * The platform's own share sheet, given something to put in front of somebody.
 *
 * The PHP face of lemonfiber's own handover. One call: here is a title and some
 * text, offer it. Where it goes is a choice a person makes in an app this one
 * does not know about, which is the whole difference between this and the crash
 * reporter this application may not have.
 *
 * **It hands over text, and writes no file.** The platform's sheet will take a
 * path, and taking one would mean this bridge writing a diagnostic report into
 * a cache directory, handing the platform a `content://` grant to read it, and
 * leaving it there — because nothing on this side ever learns when the chosen
 * app is done with it. Text needs none of that: it goes straight into whatever
 * the operator picks. Nothing in this bridge writes to a cache, and this was
 * the one capability that would have.
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
        $said = $this->answering(['title' => $title, 'text' => $text]);

        if ($this->wordUnder($said, 'outcome') === self::OFFERED) {
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
     *
     * @param array<mixed>|null $said
     */
    private function why(?array $said): WhyNothingWasHandedOver
    {
        return WhyNothingWasHandedOver::tryFrom((string) $this->wordUnder($said, 'because'))
            ?? WhyNothingWasHandedOver::ThePlatformWouldNot;
    }

    /**
     * What the bridge said, decoded, or nothing where it said nothing.
     *
     * @param array<string, string> $with
     *
     * @return array<mixed>|null
     */
    private function answering(array $with): ?array
    {
        $said = nativephp_call(Call::Offer->value, (string) json_encode($with));

        if (! is_string($said)) {
            return null;
        }

        $decoded = json_decode($said, associative: true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * One named word out of the answer, where the answer holds one.
     *
     * Written out rather than coalesced, which `C9` refuses: a `??` folds
     * absent, present-and-null and present-and-the-wrong-type into one answer,
     * and the one it picks reads as *carry on*.
     *
     * @param array<mixed>|null $said
     */
    private function wordUnder(?array $said, string $named): ?string
    {
        if ($said === null || ! array_key_exists($named, $said)) {
            return null;
        }

        $word = $said[$named];

        return is_string($word) ? $word : null;
    }
}
