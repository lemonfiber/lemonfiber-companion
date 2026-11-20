<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * A page outside the app, opened in the phone's browser.
 */
final readonly class Pages
{
    /** The address the call carries. */
    private const string ADDRESS = 'address';

    /** Open this https page in the phone's browser, and say whether it opened. */
    public function open(string $address): bool
    {
        return WhatTheBridgeAnswered::to(Call::PageOpen, [self::ADDRESS => $address])->outcome() === AppsSettings::OPENED;
    }
}
