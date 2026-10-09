<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Kernel\Api\GrantIsUnfit;
use Modules\Sdk\Api\Fields\GrantField;

use function sprintf;

/**
 * The `grant` envelope did not hold what the contract says it holds.
 *
 * {@see ShelfIsUnreadable}'s refusal, for the payload a grant is read from. A
 * developer reads it, so it is `sprintf` and never translated, and it never
 * carries the token.
 */
final class GrantIsUnreadable extends InvalidArgumentException
{
    /** A field the grant needs did not arrive, or arrived as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The grant payload carried no readable `%s`.', $field->value));
    }

    /** The token arrived in a form the door does not accept. */
    public static function unfit(GrantIsUnfit $why): self
    {
        return new self(sprintf('The grant payload carried a `%s` the door does not accept.', GrantField::Token->value), previous: $why);
    }
}
