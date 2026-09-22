<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What came back from pointing the camera at something: a code, or a reason.
 *
 * A sum type rather than a nullable string, so that a caller has to open it to
 * get at either half and the refusal cannot be the one nobody handled. The
 * refusing arm is not an error path — an operator closing the scanner is the
 * ordinary way out of it — which is exactly why an exception would be the wrong
 * instrument.
 *
 * **The refusal carries two things, and the second is the one screens need.**
 * *Refused in the dialog a moment ago* and *refused in settings some time ago*
 * are the same word and opposite advice: the first can be asked again at the
 * point of first use, and the second cannot and has to send the operator to
 * Settings. `CameraRule` decides which it is on the device; this carries the
 * answer without the screen having to know how it was reached.
 *
 * There is deliberately no `wasRead()` beside {@see self::either()}. A
 * check-then-get pair is an invitation to call the getter without the check,
 * and the forgotten one here is a screen that pairs against an empty string.
 */
final readonly class Scanned
{
    /**
     * What the payload is on the arm that has none.
     *
     * A named constant rather than a `''` written into {@see nothing()}, and
     * rather than a nullable: nullable puts a `?? ''` in {@see either()} for a
     * state the two constructors cannot produce, which is a line whose removal
     * nothing notices.
     */
    private const string NOTHING_WAS_READ = '';

    /**
     * The refusal as one field rather than two.
     *
     * {@see TheRefusal} carries the reason and whether asking again could
     * change it, which are only ever true together. Holding them apart gives
     * {@see read()} a `mayAskAgain` to name, and on that arm nothing reads it:
     * any value does, and no test can tell one from another.
     * That is the same defect {@see NOTHING_WAS_READ} is written against,
     * reached through the other field.
     */
    private function __construct(
        private string $payload,
        private ?TheRefusal $refusal,
    ) {}

    /** The camera read something. Whether it is pairing material is not asked here. */
    public static function read(string $payload): self
    {
        return new self(payload: $payload, refusal: null);
    }

    /**
     * It did not, and this is which of the three ways.
     *
     * `$mayAskAgain` is only ever true where asking could change the answer.
     * For a closed scanner that is always — they can open it again — and for a
     * refused camera it is the difference between the two sentences a screen
     * has to choose between.
     */
    public static function nothing(WhyNothingWasRead $why, bool $mayAskAgain): self
    {
        return new self(
            payload: self::NOTHING_WAS_READ,
            refusal: new TheRefusal($why, $mayAskAgain),
        );
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * The two halves of a refusal are handed over separately, which is what a
     * screen wants: it asks a different question of each. That the pair is one
     * field inside is nobody else's business.
     *
     * @template TRead of object
     * @template TNothing of object
     *
     * @param  Closure(string): TRead  $read
     * @param  Closure(WhyNothingWasRead, bool): TNothing  $nothing
     * @return TRead|TNothing
     */
    public function either(Closure $read, Closure $nothing): object
    {
        return $this->refusal instanceof TheRefusal
            ? $nothing($this->refusal->why, $this->refusal->mayAskAgain)
            : $read($this->payload);
    }
}
