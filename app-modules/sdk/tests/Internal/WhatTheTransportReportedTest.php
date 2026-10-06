<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Modules\Sdk\Internal\WhatTheTransportReported;

it('reads which kind of failure the transport reported', function (string $reported, WhatTheTransportReported $kind): void {
    expect(WhatTheTransportReported::in($reported))->toBe($kind);
})->with([
    'a name PHP could not look up' => ['php_network_getaddresses: getaddrinfo for loft.local failed: No address associated with hostname', WhatTheTransportReported::NameNotFound],
    'a name curl could not look up' => ['cURL error 6: Could not resolve host: loft.local', WhatTheTransportReported::NameNotFound],
    'a connection turned away' => ['Failed to connect to 192.168.1.42 port 8443: Connection refused', WhatTheTransportReported::Refused],
    'a wait that ran out' => ['Connection timed out after 5000 milliseconds', WhatTheTransportReported::TimedOut],
    'a secure session that was not made' => ['SSL operation failed with code 1. OpenSSL Error messages: certificate verify failed', WhatTheTransportReported::SecureSetup],
    'words nobody matches' => ['The connection was reset', WhatTheTransportReported::Other],
]);
