<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Why a screen is not showing the running release's notes, as the keys it draws.
 *
 * Empty where nothing is withheld: notes that describe the running build are
 * shown, and there is nothing to say about them.
 */
final readonly class WhatWithheldNotesSay
{
    /**
     * @param string $said      the key for why the notes are not shown, or empty where they are
     * @param string $meansSaid the key for what that means, or empty where they are
     */
    public function __construct(
        public string $said,
        public string $meansSaid,
    ) {}
}
