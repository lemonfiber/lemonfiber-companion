<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * An obstacle was asked for without what it has to carry, or with facts that describe no obstacle.
 *
 * Refused rather than drawn: a sentence with a hole where a version belongs
 * tells the operator something is wrong and not what.
 */
final class ObstacleIsNotOne extends InvalidArgumentException
{
    /** A kind that is met with facts was asked for without them. */
    public static function withoutItsFacts(KindOfObstacle $kind): self
    {
        return new self(sprintf('An obstacle of the kind `%s` is met with the facts it names, and none were given.', $kind->name));
    }

    /** Two versions that are the same, or below nought, are no disagreement. */
    public static function becauseTheVersionsAgree(int $answered, int $spoken): self
    {
        return new self(sprintf('Versions %d and %d of the API are not two versions that disagree.', $answered, $spoken));
    }
}
