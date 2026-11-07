<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** The form a starting file's placeholder writes its secret's value in. */
enum APlaceholderForm: string
{
    case Plain = 'plain';
    case Pbkdf2 = 'pbkdf2';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('plugins.placeholder_form.%s', $this->value);
    }
}
