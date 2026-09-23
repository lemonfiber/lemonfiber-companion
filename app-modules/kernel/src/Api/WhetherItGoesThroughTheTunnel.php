<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the path the line was measured over goes through the VPN tunnel.
 *
 * Two cases rather than a boolean: traffic through the tunnel and traffic
 * beside it are different facts about a household, and a row says which in
 * words.
 */
enum WhetherItGoesThroughTheTunnel: string
{
    /** Measured through the tunnel. */
    case Through = 'through';

    /** Measured beside it. */
    case Beside = 'beside';

    /** Read off the wire's boolean, which is the only place one is taken. */
    public static function said(bool $throughTunnel): self
    {
        return $throughTunnel ? self::Through : self::Beside;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.tunnel.%s', $this->value);
    }
}
