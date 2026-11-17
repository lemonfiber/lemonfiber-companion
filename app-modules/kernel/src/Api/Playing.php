<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The device's own player.
 *
 * It plays what it is handed and nothing else: everything that decides whether
 * a byte may be fetched — the door, its certificate, the member's grant — is
 * the core's, carried in {@see ATitleToPlay}. Once on screen it is the
 * member's: they pause it, seek in it and close it on the device, and the app
 * learns where it stands by asking.
 */
interface Playing
{
    /** Put the player on screen for one title, or say why not. */
    public function open(ATitleToPlay $title): WhatOpeningCameTo;

    /**
     * Put the player on screen for a channel's live stream, or say why not.
     *
     * The player decides which of the stream's addresses it can decode, the
     * tuner's own first. A channel has no place to start from or to keep.
     */
    public function tune(AChannelToPlay $channel): WhatOpeningCameTo;

    /** Where the player stands now. */
    public function whereItStands(): WherePlayingStands;

    /** Take the player off screen, whether or not one was on it, and answer where it stands then. */
    public function close(): WherePlayingStands;
}
