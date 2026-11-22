<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** What kind of thing one declaration of the operator's leaves alone. */
enum WhatIsLeftAlone: string
{
    /** A service the stack runs. */
    case Service = 'service';

    /** A file or folder the stack maintains. */
    case Path = 'path';

    /** A setting the stack would otherwise write. */
    case Setting = 'setting';

    /** The catalogue key for this kind, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.left_alone.kind.%s', $this->value);
    }
}
