<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A yes was built against an answer that asked for none.
 *
 * A confirmation is only ever about something the stack put in front of the
 * operator: a choice it held, an upgrade it described without carrying out,
 * a way of moving in it staged, a run its record shows can go back, a
 * preview of putting the configuration back that would revert something, or a
 * choice of what fills a capability worked out and not yet made.
 * Building one against anything else means a screen offered a yes it should
 * not have, which is a fault in the surface rather than a situation the
 * operator can resolve.
 */
final class ThereIsNothingToAgreeTo extends InvalidArgumentException
{
    /** The choice was not held, so there is nothing waiting on a confirmation. */
    public static function held(WhatBecameOfTheChoice $became): self
    {
        return new self(sprintf(
            'A quality choice was confirmed where the stack answered `%s`, and only a held choice waits on a confirmation.',
            $became->value,
        ));
    }

    /** The upgrade was already carried out, so there is no description to agree to. */
    public static function described(): self
    {
        return new self('An upgrade was agreed to against one already carried out, and only a described upgrade waits on a yes.');
    }

    /** A way of moving in was not staged, so nothing waits on a yes. */
    public static function staged(Stance $stance): self
    {
        return new self(sprintf(
            'A way of moving in was agreed to where the stack answered `%s`, and only a pending move waits on a yes.',
            $stance->value,
        ));
    }

    /** The record holds nothing of the run, or says part of it cannot go back, so the stack would put none of it back. */
    public static function aRun(ARun $run): self
    {
        return new self(sprintf(
            'Putting back the run stamped `%s` was agreed to where the record holds none of it or says part of it cannot go back, and the stack puts back a whole run or nothing.',
            $run->stamp(),
        ));
    }

    /** Putting the configuration back was already carried out, so there is no preview to agree to. */
    public static function reverted(): self
    {
        return new self('A reset was agreed to against one already carried out, and only a preview waits on a yes.');
    }

    /** The choice of what fills a capability was already made, so there is no reading to agree to. */
    public static function made(): self
    {
        return new self('A choice of what fills a capability was agreed to against one already made, and only a reading waits on a yes.');
    }

    /** The preview reverts no file and no connection, so a yes would agree to nothing. */
    public static function nothingToRevert(): self
    {
        return new self('A reset was agreed to against a preview that reverts nothing, and a yes to nothing is not asked for.');
    }
}
