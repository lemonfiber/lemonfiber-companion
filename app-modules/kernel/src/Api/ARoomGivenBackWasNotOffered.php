<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * A yes to giving back a category the offer on the screen does not hold.
 */
final class ARoomGivenBackWasNotOffered extends LogicException
{
    /** That category was agreed to with no offer of it to quote. */
    public static function as(WhatALineIsAbout $category): self
    {
        return new self(sprintf('Giving back `%s` was agreed to, and the offer read holds no such category.', $category->value));
    }
}
