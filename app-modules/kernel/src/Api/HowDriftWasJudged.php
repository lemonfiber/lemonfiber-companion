<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a wiring run could judge drift, in the stack's word for it.
 *
 * *Unassessable* is a third answer beside sound and broken: the record of what
 * lemonfiber last wrote was there and could not be read, so nothing this run
 * says about drift was judged against it.
 */
enum HowDriftWasJudged: string
{
    /** Each connection was judged against the record of what lemonfiber last wrote. */
    case Assessed = 'assessed';

    /** The record could not be read, so drift could not be judged this run. */
    case Unassessable = 'unassessable';
}
