<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Taking somebody out, agreed to having been shown what it costs.
 *
 * The only way to make one is against a reading nobody has agreed to yet,
 * which is what makes the person taken out the person whose cost was shown:
 * the name carried is the one the stack answered that reading under, as the
 * media server spells it.
 *
 * It names the person and nothing else. The stack takes a bare yes for a
 * removal, so nothing sent can say which reading was agreed to; the screen
 * holds this for as long as the reading is in front of the operator and no
 * longer.
 */
final readonly class ARemovalAgreed
{
    private function __construct(private SomebodyInTheHousehold $who) {}

    /** Agreed against that reading; an answer that was already carried out is refused. */
    public static function after(ARemoval $described): self
    {
        if ($described->wasCarriedOut()) {
            throw RemovalWasNotDescribed::becauseItWasCarriedOut();
        }

        return new self($described->who());
    }

    /** Who is taken out. */
    public function who(): SomebodyInTheHousehold
    {
        return $this->who;
    }
}
