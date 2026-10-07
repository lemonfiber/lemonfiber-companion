<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhetherItIsOffered;
use RuntimeException;

use function sprintf;

/**
 * A request the stack does not declare, refused before it was sent.
 *
 * Raised by {@see GatedClient} and read by every adapter as what stood in the
 * way, so the screen says the stack is too old for it, or that it is not this
 * account's, rather than showing the refusal of a request nothing sent.
 */
final class TheStackDoesNotOfferIt extends RuntimeException
{
    private function __construct(string $message, private readonly WhetherItIsOffered $offered)
    {
        parent::__construct($message);
    }

    /** The stack does not offer the request at this path, as the stack said. */
    public static function at(Ability $path, WhetherItIsOffered $offered): self
    {
        return new self(sprintf('The stack does not offer %s (%s), so it was not asked.', $path->named(), $offered->value), $offered);
    }

    /**
     * What the operator met: a stack too old to have it, or an account it is not for.
     *
     * The second is the obstacle the stack itself answers with when it refuses
     * an account, so a screen says the same thing whether the stack said it
     * before the request or in answer to one.
     */
    public function obstacle(): Obstacle
    {
        return Obstacle::of($this->offered === WhetherItIsOffered::NotTheirs ? KindOfObstacle::NotForThisAccount : KindOfObstacle::NotOnThisStack);
    }
}
