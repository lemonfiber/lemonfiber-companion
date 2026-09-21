<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function nativephp_call;

/**
 * Whether this device can reach anything at all right now.
 *
 * The PHP face of lemonfiber's own link check. One call, two answers, and the
 * whole point of it is a distinction nothing else can draw: *this phone has no
 * network* and *that machine is not answering* look identical to a socket that
 * timed out, and they are two sentences with two different remedies. Telling
 * somebody to go and check their machine when they are in a lift is the kind of
 * wrong that makes an app feel stupid.
 *
 * **Nothing about the device comes back.** Not the kind of link, not whether it
 * is metered, not whether Low Data Mode is on. Both platforms report all of it
 * and the envelope has no place to put any of it, so adding one means
 * explaining why rather than uncommenting a line.
 *
 * **Off a handset this answers reachable, which is the opposite of what every
 * other capability here does.** The others report the least they can, because
 * claiming a capability that is not there makes a test pass on a machine that
 * cannot do the thing. This one is the other way round: reading *no answer* as
 * *no network* would put every desktop and every test run into a state whose
 * remedy is *turn your wifi on*, which is both wrong and unactionable. Reading
 * it as reachable means the app tries, and a stack it cannot reach is reported
 * the way it always was — one attempt wasted, and the honest answer.
 */
final readonly class Link
{
    /** The one word that means no. Anything else, including silence, means yes. */
    private const string UNREACHABLE = 'unreachable';

    /**
     * Whether anything is reachable from here.
     *
     * A bool rather than a word because there is no third thing to say, and
     * because the one caller of this is choosing between two sentences. Which
     * of the two refusals it was is not a question this capability has: a
     * device with no link and a device whose platform would not answer are the
     * same answer here, and they are told apart by the answer being *yes*.
     */
    public function isReachable(): bool
    {
        // Compared against the refusal rather than the affirmative, so that
        // every other answer — a word this build does not know, a malformed
        // envelope, no bridge at all — lands on *try anyway*. The affirmative
        // spelling would make silence mean *no network*, which is the reading
        // that puts every desktop and every test run behind a wifi warning.
        return $this->wordUnder($this->answering(), 'outcome') !== self::UNREACHABLE;
    }

    /**
     * What the bridge said, decoded, or nothing where it said nothing.
     *
     * **This is the one call here that carries nothing**, and it says so by
     * passing nothing rather than by encoding an empty array. `[]` and `{}`
     * and `''` all arrive at both native halves as no parameters — `Status`
     * reads none on either platform — so the three were interchangeable, and a
     * payload nothing can tell apart from another is a payload no test can
     * hold. `nativephp_call` declares `'{}'` for exactly this case, which is
     * also the shape a `Map<String, Any>` on the Kotlin side is written for.
     *
     * Every other call in this package encodes something somebody passed in,
     * where an unencodable payload is a real answer and is refused as one.
     *
     * @return array<mixed>|null
     */
    private function answering(): ?array
    {
        $said = nativephp_call(Call::LinkStatus->value);

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
