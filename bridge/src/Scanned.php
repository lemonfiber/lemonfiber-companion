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

    private function __construct(
        private string $payload,
        private ?WhyNothingWasRead $why,
        private bool $mayAskAgain,
    ) {}

    /** The camera read something. Whether it is pairing material is not asked here. */
    public static function read(string $payload): self
    {
        return new self(payload: $payload, why: null, mayAskAgain: false);
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
        return new self(payload: self::NOTHING_WAS_READ, why: $why, mayAskAgain: $mayAskAgain);
    }

    /**
     * Say what happens either way, and get back what you built.
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
        return $this->why instanceof WhyNothingWasRead
            ? $nothing($this->why, $this->mayAskAgain)
            : $read($this->payload);
    }
}
