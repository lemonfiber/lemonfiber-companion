<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Kernel\Api\ReleaseIsNoDay;
use Modules\Sdk\Api\Fields\TitleField;

use function sprintf;

/**
 * The `title` envelope did not hold what the contract says it holds.
 *
 * {@see ShelfIsUnreadable}'s refusal, for one title read in full. A developer
 * reads it, so it is `sprintf` and never translated.
 */
final class TitleIsUnreadable extends InvalidArgumentException
{
    /** A field arrived missing, or as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The title payload carried no readable `%s`.', $field->value));
    }

    /** The day it came out is not one the calendar has. */
    public static function noDay(ReleaseIsNoDay $why): self
    {
        return new self(sprintf('The title payload carried a `%s` the calendar does not have.', TitleField::Released->value), previous: $why);
    }

    /** One season could not be read, named by where it sat. */
    public static function season(int $season): self
    {
        return new self(sprintf('The season at position %d could not be read.', $season));
    }

    /** One episode could not be read, named by where it sat in its season. */
    public static function episode(int $season, int $episode): self
    {
        return new self(sprintf('The episode at position %d of the season at position %d could not be read.', $episode, $season));
    }
}
