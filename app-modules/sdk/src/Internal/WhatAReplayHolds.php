<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** What a `replay` says of the alerts that follow it, by the word the wire spells it with. */
enum WhatAReplayHolds: string
{
    /** Every alert raised since the id the stream was opened after. */
    case EveryAlert = 'whole';

    /** The alerts still kept, where some raised since are not. */
    case ThoseStillKept = 'partial';
}
