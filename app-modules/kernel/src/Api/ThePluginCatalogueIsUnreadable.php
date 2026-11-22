<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The stack's catalogue reading arrived with a word this app cannot take as it is: a blank where a fact is owed, or a setting written by an act this app does not know.
 */
final class ThePluginCatalogueIsUnreadable extends InvalidArgumentException
{
    /** One of its words was blank, named because there are many. */
    public static function blank(string $field): self
    {
        return new self(sprintf('The catalogue arrived with its `%s` blank, and an entry that will not say what it is is not one to offer.', $field));
    }

    /** The setting that turns reaching it on is written by an act this app does not know, so it is offered to nobody. */
    public static function writtenBy(string $action): self
    {
        return new self(sprintf('The catalogue named `%s` as the act that turns reaching it on, which this app does not know how to put to a review.', $action));
    }
}
