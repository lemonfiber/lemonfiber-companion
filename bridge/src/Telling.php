<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_filter;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * The operator's notification centre, as this application is willing to use one.
 *
 * The PHP face of lemonfiber's own notifications. Nine calls, each one a
 * message to the Kotlin or the Swift beside it; the decisions about *whether*
 * anything may be shown and *when* a repeat comes round are made there, in
 * `NotificationRule` and `Recurrence`, where they can be unit-tested without a
 * handset.
 *
 * **Local, never pushed.** A pushed payload travels through Google's or Apple's
 * relay to reach the device, which is a third party reading what a stack said
 * about somebody's home. Nothing here can push and nothing here has an address
 * to push to.
 *
 * **Reading is a separate call from asking, and that is the whole design.** An
 * application that cannot read the standing answer without raising a prompt has
 * no way to obey one: reading becomes asking, and a device where somebody
 * already refused gets asked again every time a screen opens. So
 * {@see self::standing()} reads and {@see self::ask()} prompts.
 *
 * **Off a handset every call reports the least it can.** Nothing is shown and
 * nobody has been asked. That is honest rather than convenient: a stand-in
 * claiming otherwise would make a test about notifications pass on a machine
 * that cannot show one.
 */
final readonly class Telling
{
    /**
     * The one word this capability answers a refusal with.
     *
     * A named constant rather than a literal beside `===`, and not an enum,
     * because it is not a set: every function here answers exactly one word for
     * a refusal and its own word for a success — `shown`, `scheduled`,
     * `cancelled`, `cleared`, `read`. Listing the successes instead would mean
     * a sixth of them, added on the wire, quietly reading as a refusal.
     *
     * The word differs per capability — a write is `refused`, a scan that found
     * nothing is `nothing` — which is why it belongs to this class rather than
     * to the bridge.
     */
    private const string WITHHELD = 'withheld';

    /** What the operator has already said, read without raising anything. */
    public function standing(): WhatTheOperatorSaid
    {
        return WhatTheOperatorSaid::orNothingSaid($this->outcomeOf(Call::Standing));
    }

    /**
     * Raise the prompt, and answer what the operator said.
     *
     * Waits for them rather than returning while the dialog is on screen. Both
     * native halves record that the prompt went up before raising it — which is
     * the fact nothing in Android can otherwise supply — and reading that
     * record back before the operator had answered would report a refusal about
     * somebody still looking at the question.
     */
    public function ask(): WhatTheOperatorSaid
    {
        return WhatTheOperatorSaid::orNothingSaid($this->outcomeOf(Call::Ask));
    }

    /** Put something in front of the operator now, or say why not. */
    public function show(string $id, string $title, string $body): Told
    {
        return $this->told(Call::Show, ['id' => $id, 'title' => $title, 'body' => $body]);
    }

    /**
     * Put something in front of them at a moment, or say why not.
     *
     * The moment is counted in seconds since the epoch, which is what both
     * platforms' own schedulers take. A moment that has already been is
     * withheld rather than shown immediately: an alert about something that was
     * going to happen is not an alert about something that has.
     */
    public function schedule(string $id, string $title, string $body, int $at): Told
    {
        return $this->told(Call::Schedule, ['id' => $id, 'title' => $title, 'body' => $body, 'at' => $at]);
    }

    /** Put something in front of them over and over, or say why not. */
    public function scheduleRecurring(string $id, string $title, string $body, Repeat $repeat): Told
    {
        return $this->told(
            Call::ScheduleRecurring,
            ['id' => $id, 'title' => $title, 'body' => $body, ...$repeat->asAsked()],
        );
    }

    /**
     * Take one back, whether it is showing, scheduled or neither.
     *
     * Taking back something that was never scheduled is done rather than an
     * error, for the reason forgetting a key that was never kept is forgotten:
     * it is the ordinary case after a refusal, and getting rid of something is
     * the one operation that must always work.
     */
    public function cancel(string $id): Told
    {
        return $this->told(Call::Cancel, ['id' => $id]);
    }

    /** Take back everything this application scheduled. */
    public function cancelAll(): Told
    {
        return $this->told(Call::CancelAll);
    }

    /** Take the count off this application's icon. */
    public function clearBadge(): Told
    {
        return $this->told(Call::ClearBadge);
    }

    /**
     * What is still to come, or nothing where nobody answered.
     *
     * Null rather than an empty list, and the difference is the one a caller
     * actually has: *nothing is scheduled* and *this process cannot ask what is
     * scheduled* are opposite answers, and a machine that is not a handset
     * gives the second. Nothing in this application reads this yet; the day
     * something does, it reads a value that can say so.
     *
     * @return list<string>|null
     */
    public function pending(): ?array
    {
        $said = $this->answering(Call::Pending);

        if (! is_array($said) || ! array_key_exists('pending', $said) || ! is_array($said['pending'])) {
            return null;
        }

        return array_values(array_filter($said['pending'], is_string(...)));
    }

    /**
     * One call, reduced to what became of it.
     *
     * @param array<string, int|string> $with
     */
    private function told(Call $function, array $with = []): Told
    {
        $said = $this->answering($function, $with);
        $outcome = $this->wordUnder($said, 'outcome');

        return $outcome === null || $outcome === self::WITHHELD
            ? Told::withheld(WhyNothingWasTold::orTheDeviceRefused($this->wordUnder($said, 'because')))
            : Told::done();
    }

    /**
     * The outcome word of one call, or nothing where there was no answer.
     *
     * @param array<string, int|string> $with
     */
    private function outcomeOf(Call $function, array $with = []): ?string
    {
        return $this->wordUnder($this->answering($function, $with), 'outcome');
    }

    /**
     * One word out of an answer, or nothing where there is no word there.
     *
     * Asked for rather than defaulted. `??` on a decoded answer folds absent,
     * present-and-null and present-and-the-wrong-type into one value, and every
     * caller above has a different thing to do about *nobody answered* than
     * about *answered with something unexpected* (`C9`).
     */
    private function wordUnder(mixed $said, string $key): ?string
    {
        if (! is_array($said) || ! array_key_exists($key, $said) || ! is_string($said[$key])) {
            return null;
        }

        return $said[$key];
    }

    /**
     * One bridge call, decoded.
     *
     * `nativephp_call()` is the bridge. On a handset it is a C extension
     * function; on a development machine `nativephp/mobile` supplies a fallback
     * that relays to a connected device, and answers
     * `{"status":"error","code":"NO_DEVICE"}` when there is none. Under test a
     * bound `FakeBridge` intercepts it in-process.
     *
     * All of those, plus an unparsable answer, arrive here as something that is
     * not an array with an `outcome` in it — and every caller above reads that
     * as the least it could mean. Collapsing them is right rather than lazy: no
     * caller would do something different for each, and four ways to say
     * "nobody answered" is four chances to check only three of them.
     *
     * @param array<string, int|string> $with
     */
    private function answering(Call $function, array $with = []): mixed
    {
        return json_decode(
            (string) nativephp_call($function->value, (string) json_encode($with)),
            associative: true,
        );
    }
}
