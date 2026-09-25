<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What a service left out of a form would have needed, in the stack's words.
 *
 * The reason a service is filtered rather than failed: a form's profiles are
 * intersected with what the operator configured, so a machine with no torrent
 * credentials leaves the torrent profiles out on purpose. Said, it is the
 * feature working; unsaid, it is a silence an operator goes looking into.
 */
enum WhatItWouldNeed: string
{
    /** Credentials for a Usenet provider, which the stack does not have. */
    case Usenet = 'usenet';

    /** Credentials for torrenting, which the stack does not have. */
    case Torrent = 'torrent';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()}.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.rehearsal.needs.%s', $this->value);
    }
}
