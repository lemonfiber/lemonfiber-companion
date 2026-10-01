<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

/** What came of removing a stack from the phone. */
enum WhatBecameOfRemoving
{
    /** Nothing of the stack is kept any longer: its pairing, session, readings, settings and markers are gone. */
    case Removed;

    /**
     * The removal began and something of the stack is still kept.
     *
     * It is finished when the app next opens, and until then it is as good as
     * removed: the stack is not drawn anywhere once its pairing has gone.
     */
    case Finishing;

    /** The removal could not begin, and nothing of the stack was touched. */
    case Refused;
}
