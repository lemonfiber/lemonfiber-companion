<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The UnifiedPush distributors on this phone, the endpoint each gives a stack, and the WebPush key pair the phone makes for it.
 *
 * Each stack is registered with the distributor on its own, under its id, and
 * its key pair is made in the phone's keystore for it alone. A push that
 * arrives wakes the app for that stack and is never read.
 */
interface ReachingADistributor
{
    /** The distributors installed, or that this phone cannot be woken through one. */
    public function distributors(): WhatThisPhoneWakesThrough;

    /** Make a key pair for the stack and ask the distributor for an endpoint, and answer both, or why there is none; nothing is left behind where there is none. */
    public function endpointFor(StackId $stack, ADistributor $distributor): AnEndpointGiven;

    /** The endpoint the distributor gives the stack now, which it may have changed since it was registered, or why there is none. */
    public function endpointNowFor(StackId $stack): AnEndpointGiven;

    /** Drop the distributor's registration for the stack and delete its key pair, and answer whether the phone let go. */
    public function letGo(StackId $stack): bool;

    /** Drop every distributor registration and delete every key pair made for waking, and answer whether the phone let go. */
    public function letGoOfEvery(): bool;
}
