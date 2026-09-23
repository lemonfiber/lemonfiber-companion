<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A service's origin arrived saying less than an operator is entitled to know.
 *
 * Refused rather than shown, for {@see ChangeSaysNothing}'s reason one surface
 * along. Where a service comes from is asked by somebody deciding whether to
 * trust what runs on their own machine, and a row with a blank where the
 * licence or the upstream belongs is the answer that sounds complete and is
 * not.
 */
final class OriginSaysNothing extends InvalidArgumentException
{
    /**
     * One of its words was blank.
     *
     * The field is named because a stack can declare many services, and a
     * refusal naming nothing is the defect it is refusing.
     */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A service arrived with its `%s` blank, and an origin that will not say what it is is worse than none: it reads as complete to somebody deciding what to trust.',
            $field,
        ));
    }

    /**
     * One service was declared twice.
     *
     * A stack names each service once, so a second entry under the same name
     * is two origins for one thing — and whichever a screen drew, the other
     * would be the one somebody checks.
     */
    public static function twice(ServiceId $service): self
    {
        return new self(sprintf(
            'The service `%s` was declared twice, and one service with two origins is a question with two answers.',
            $service->named(),
        ));
    }
}
