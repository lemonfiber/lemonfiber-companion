<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `forms` envelope did not hold what the contract says it holds.
 *
 * {@see RosterIsUnreadable}'s sibling, for the list of forms, and for its
 * reason: each of these is a bug somewhere other than here, and the message
 * names what arrived because that is the only thing that shortens the search. A
 * developer reads it, so it is `sprintf` and never translated.
 *
 * **Refused rather than dropped.** A form missing from the list is a form an
 * operator cannot start from the phone, with nothing on the screen to say so.
 */
final class RepertoireIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The forms envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function item(int $position): self
    {
        return new self(sprintf(
            'Form %d in the forms envelope is not a form. It is refused rather than dropped: a list one form short is a form an operator cannot start, and nothing on the screen would say so.',
            $position,
        ));
    }

    public static function unnamed(int $position): self
    {
        return new self(sprintf(
            'Form %d in the forms envelope has no readable `%s`. The id is what a verb asks for a form by, so a form without one is a control that could only send a blank.',
            $position,
            WireField::Id->value,
        ));
    }
}
