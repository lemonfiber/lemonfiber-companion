<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What there is to do next about a hand-off, named by the stack and said by each surface its own way.
 *
 * The stack carries the act rather than a sentence because the way to take it
 * differs: a terminal names a command, and this app offers a control or,
 * where it has none, says nothing beyond the stack's reason.
 *
 * `Nothing` is the stack naming no remedy, which it does by sending none: its
 * value is the empty word, so no remedy the wire names can be read as it.
 */
enum WhatTheHandoffNeedsNext: string
{
    /** Nothing is left to do, or nothing this app can offer. */
    case Nothing = '';

    /** Invite them, which makes the account. */
    case Invite = 'invite';

    /** Ask again: once they have signed in, or once the media server answers. */
    case AskAgain = 'ask-again';

    /** See whether the media server is running. */
    case StartServer = 'start-server';

    /** Record the address the household reaches this machine at. */
    case RecordAddress = 'record-address';
}
