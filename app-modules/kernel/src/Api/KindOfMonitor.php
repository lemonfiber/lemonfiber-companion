<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How the outside watcher looks at what one monitor covers.
 *
 * The value is the wire's word.
 */
enum KindOfMonitor: string
{
    /** It asks an address over the web. */
    case Http = 'http';

    /** It asks an address and looks for a word in the answer. */
    case Keyword = 'keyword';

    /** It opens a port. */
    case Port = 'port';

    /** It pings a host. */
    case Ping = 'ping';

    /** It resolves a name. */
    case Dns = 'dns';

    /** It asks the container engine. */
    case Docker = 'docker';

    /** It waits for the stack to check in. */
    case Heartbeat = 'heartbeat';

    /** The key for this kind, on the watcher screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('watcher.kind.%s', $this->value);
    }
}
