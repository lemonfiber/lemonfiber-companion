<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

/**
 * What became of the phone's saved data when the app opened.
 *
 * Two answers, because there is one thing to say and one case that says it:
 * the data was cleared because it could no longer be read, which the operator
 * is told once, or nothing happened to it, which is not worth a word.
 */
enum WhatWasKeptAtOpening
{
    /** Still readable, or never kept, or kept nowhere: nothing to say. */
    case AsItWas;

    /** Cleared, because the key that sealed it had gone and a new one was made. */
    case Cleared;
}
