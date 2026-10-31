<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Replacing the certificate the media server presents at its internal TLS port.
 *
 * One act, sent as `internal-certificate-replace`: read first with no offer,
 * then sent again with the offer the reading named.
 */
enum ReplacingACertificate: string implements AnAction
{
    case Replace = 'internal-certificate-replace';

    public function asked(): string
    {
        return $this->value;
    }
}
