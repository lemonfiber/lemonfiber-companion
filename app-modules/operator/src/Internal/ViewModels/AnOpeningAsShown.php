<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One thing a change opens, in the stack's words: an account to obtain, or the setting it is kept in. */
final readonly class AnOpeningAsShown
{
    /** @param string $setting the setting it is kept in, or empty for an account to obtain */
    public function __construct(public string $what, public string $because, public string $setting) {}
}
