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
 * It names the person and the reading agreed to, by the name the stack gave
 * it, so the stack refuses a yes given for a household that has moved since.
 */
final readonly class ARemovalAgreed
{
    private function __construct(private SomebodyInTheHousehold $who, private string $offer) {}

    /** Agreed against that reading; an answer that was already carried out is refused. */
    public static function after(ARemoval $described): self
    {
        if ($described->wasCarriedOut()) {
            throw RemovalWasNotDescribed::becauseItWasCarriedOut();
        }

        return new self($described->who(), $described->offer());
    }

    /** Who is taken out. */
    public function who(): SomebodyInTheHousehold
    {
        return $this->who;
    }

    /** The name of the reading agreed to, which the yes quotes. */
    public function offer(): string
    {
        return $this->offer;
    }
}
