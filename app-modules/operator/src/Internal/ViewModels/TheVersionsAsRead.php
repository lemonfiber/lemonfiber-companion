<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The versions the stack answered with, and the running release's notes or why they are not shown. */
final readonly class TheVersionsAsRead
{
    /**
     * @param string                                      $lemonfiber the version of lemonfiber
     * @param string                                      $stack      the version of the stack
     * @param string                                      $engine     what the container engine reports, or empty where it could not be asked
     * @param TheReleaseNotesAsShown|WhatWithheldNotesSay $notes      the notes, or why they are not shown
     */
    public function __construct(
        public string $lemonfiber,
        public string $stack,
        public string $engine,
        public TheReleaseNotesAsShown|WhatWithheldNotesSay $notes,
    ) {}
}
