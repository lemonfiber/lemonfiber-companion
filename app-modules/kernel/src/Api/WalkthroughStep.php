<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One step of a walkthrough, in the order the walk takes them.
 *
 * Its own set rather than {@see Stage}, because the contract gives the walk a
 * vocabulary of its own: choosing something and telling the media server to
 * look at it are steps of the walk and no stage an item rests at.
 *
 * **It carries no sentence of its own.** A step is one of lemonfiber's words,
 * drawn as the glossary gives it and explained by the glossary where the
 * glossary carries it. A sentence written here would be this app defining a
 * word the glossary does not.
 */
enum WalkthroughStep: string
{
    /** Picking something to add, and confirming it is not already here. */
    case Choosing = 'choosing';

    /** The indexers are being searched for releases. */
    case Searching = 'searching';

    /** A release is being sent to the download client. */
    case Grabbing = 'grabbing';

    /** The download is running. */
    case Downloading = 'downloading';

    /** The finished download is being moved into the library. */
    case Importing = 'importing';

    /** The media server is being told to look at what arrived. */
    case Scanning = 'scanning';

    /** It is in the library and playable. */
    case Available = 'available';
}
