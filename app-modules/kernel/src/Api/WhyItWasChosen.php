<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Why a contested capability was settled the way it was — or the word for
 * nobody having said.
 *
 * A type rather than a nullable string, which is what keeps *not read yet* and
 * *read, and there was nothing* from arriving as the same value. The core marks
 * the field optional, so a settlement with no reason is ordinary rather than a
 * fault; what is not ordinary is a screen that cannot tell the two apart, and a
 * `?string` is exactly that told apart by an `instanceof` the caller cannot see.
 *
 * {@see TurnedDown} is the same device one noun over, for a refusal's moment.
 *
 * **Nothing is normalised on the way in.** A blank reason is refused rather
 * than quietly turned into {@see self::unstated()}, because those are different
 * claims: an absent field is the core saying nobody explained, and a blank one
 * is the core sending a broken value. Turning the second into the first would
 * hide a fault in the thing the app is meant to render faithfully. Where the
 * wire carries an absent or empty field, the adapter that reads it says
 * {@see self::unstated()} — which is a decision somebody wrote rather than a
 * gap, the argument {@see Services::none()} makes about an empty list.
 */
final readonly class WhyItWasChosen
{
    private function __construct(private ?string $why) {}

    /**
     * Somebody said why, and these are their words.
     *
     * Carried as they stand rather than rewritten, for {@see TurnedDown}'s
     * reason: the operator reads the same machine through the stack's own
     * interfaces, and a second wording here would be a second vocabulary for
     * one decision.
     */
    public static function stated(string $why): self
    {
        $reason = trim($why);

        if ($reason === '') {
            throw ASettlementSaysNothing::whereAReasonWasClaimed();
        }

        return new self($reason);
    }

    /** Nobody said why, which the core is allowed to answer and a screen has a sentence for. */
    public static function unstated(): self
    {
        return new self(null);
    }

    /**
     * Say what the reason was, or say that nobody gave one.
     *
     * @template TStated of object
     * @template TUnstated of object
     *
     * @param  Closure(string): TStated  $stated
     * @param  Closure(): TUnstated  $unstated
     * @return TStated|TUnstated
     */
    public function saying(Closure $stated, Closure $unstated): object
    {
        $why = $this->why;

        return $why === null ? $unstated() : $stated($why);
    }
}
