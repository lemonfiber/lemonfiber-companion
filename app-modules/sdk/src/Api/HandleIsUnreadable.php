<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `job` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see OfferIsUnreadable} is, for the one envelope every
 * action that reaches the services is answered with: a developer reads it, so
 * it is `sprintf` and never translated (`L1`), and the message names the field
 * because that is the only thing that shortens the search.
 *
 * **Apart from the refusals of the payloads that arrive through it**, because
 * the handle is not the answer. A repair, a start and a stop each end in a
 * different envelope and share this one, so a refusal named for any of them
 * would be the wrong sentence in front of a developer two thirds of the time.
 */
final class HandleIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The acknowledgement has no `%s`, or it is not what the contract says it is. This did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }
}
