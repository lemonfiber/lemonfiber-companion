<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app asks of a stack about pairing another phone with it.
 *
 * One act: making a fresh pairing code. It carries no credential and admits
 * nobody, and replacing the certificate a paired phone pins is not offered.
 */
enum WhatToDoAboutPairing: string implements AnAction
{
    /** Make a fresh pairing code another phone adds the stack with. */
    case MakeACode = 'make_a_code';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::MakeACode => 'companion-pair',
        };
    }
}
