<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * This app's own page in the phone's settings, asked for through lemonfiber's own call.
 *
 * Not the framework's `System.OpenAppSettings`, whose PHP side discards what
 * the handset answered: a screen whose button did nothing has to be able to
 * say so, so this asks a call that answers.
 *
 * **Only an answer that the page was asked for is a yes.** A refusal, a word
 * this build does not know, a malformed envelope, or no bridge at all reads as
 * the page not having opened, so the screen says so rather than leaving the
 * operator waiting for a page that never came.
 */
final readonly class AppsSettings
{
    /** The one word that means the page was asked for. */
    private const string OPENED = 'opened';

    /** Ask for this app's settings page, and answer whether it opened. */
    public function open(): bool
    {
        return WhatTheBridgeAnswered::toNothing(Call::SettingsOpen)->outcome() === self::OPENED;
    }
}
