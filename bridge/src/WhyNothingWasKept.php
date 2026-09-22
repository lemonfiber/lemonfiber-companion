<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the device's secure store would not do as it was asked.
 *
 * Two refusals, and they reach the operator as two screens with two remedies.
 * *This phone cannot do this* is answered by saying so and never asking again;
 * *the store would not open* is answered by trying again, and by something being
 * wrong with the device if it keeps happening.
 *
 * A boolean cannot carry that, which is the whole argument for the word: an
 * adapter behind a bool has to infer which it was, and the inference is wrong
 * exactly when it matters.
 */
enum WhyNothingWasKept: string
{
    /** There is no secure store on this device. Nothing will fix it. */
    case NoStoreOnThisDevice = 'no_store_on_this_device';

    /** There is one and it would not open. Trying again is reasonable. */
    case StoreWouldNotOpen = 'store_would_not_open';

    /**
     * What the bridge said, or that it said nothing this type recognises.
     *
     * A bridge with no device behind it answers nothing at all, which is every
     * machine that is not a handset. Both that and an unrecognised word are
     * read as {@see self::StoreWouldNotOpen}, which is the recoverable of the
     * two: it tells an operator to try again, where the other tells them to
     * give up on the phone. Being wrong in the first direction costs a retry;
     * being wrong in the second costs them the application.
     *
     * **Two branches rather than `tryFrom($said ?? '')`.** Routing *nothing
     * said* through the empty string makes it the same path as a word this
     * type does not know, and the two are not the same thing — one is a
     * machine with no device behind it, the other is a bridge that grew a case
     * since this was written. Nothing could tell them apart either: the empty
     * string is not a case here, so every unrecognised word already lands where
     * it lands and swapping one for another changes nothing anybody can
     * observe. That is a line no test can hold, which is what the mutation
     * floor said about it. `WhatTheBridgeSaidTest` drives both.
     */
    public static function orTheStoreWouldNotOpen(?string $said): self
    {
        if ($said === null) {
            return self::StoreWouldNotOpen;
        }

        return self::tryFrom($said) ?? self::StoreWouldNotOpen;
    }
}
