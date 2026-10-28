<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the core and the stream have told the operator of what is playing: whether the stream broke, and what was heard, or null where nothing was yet.
 */
final readonly class WhatTheOperatorHearsPlaying
{
    public function __construct(public bool $broke, public ?WhatWasHeardPlaying $heard) {}
}
