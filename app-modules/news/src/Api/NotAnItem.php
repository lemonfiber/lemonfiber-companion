<?php

declare(strict_types=1);

namespace Modules\News\Api;

use InvalidArgumentException;

use function sprintf;

/** What could not be made an item of news, and why. */
final class NotAnItem extends InvalidArgumentException
{
    /** An update, or a problem's check, named by nothing. */
    public static function namedByNothing(KindOfNews $kind): self
    {
        return new self(sprintf('An item of the kind %s is named by nothing.', $kind->value));
    }

    /** A request numbered below one, which the household's requests never are. */
    public static function numbered(int $number): self
    {
        return new self(sprintf('A request is numbered from one, and this one was numbered %d.', $number));
    }

    /** An item in a list of another kind. */
    public static function ofAnotherKind(KindOfNews $listed, KindOfNews $item): self
    {
        return new self(sprintf('A list of %s items was handed an item of the kind %s.', $listed->value, $item->value));
    }
}
