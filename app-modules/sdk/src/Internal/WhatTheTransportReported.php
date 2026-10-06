<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_any;
use function mb_strtolower;
use function str_contains;

/**
 * Which kind of failure a reach that met nothing reported, read off the
 * transport's own words, and nothing else of them.
 *
 * The words name the address they failed on, so they are never kept or
 * written down; only which kind they were is. A note for a developer, not a
 * judgement a screen makes: every kind here still reaches an operator as the
 * same obstacle.
 */
enum WhatTheTransportReported: string
{
    /** The name could not be turned into an address on this phone. */
    case NameNotFound = 'name_not_found';

    /** Something at the address turned the connection away. */
    case Refused = 'refused';

    /** Nothing came back before the wait ran out. */
    case TimedOut = 'timed_out';

    /** The connection was made and the secure session on it was not. */
    case SecureSetup = 'secure_setup';

    /** Words none of the others match. */
    case Other = 'other';

    /** What each kind's words contain, as the transports on this phone write them. */
    private const array WORDS = [
        'name_not_found' => ['getaddrinfo', 'could not resolve host', 'no address associated', 'name or service not known', 'nodename nor servname'],
        'refused' => ['connection refused'],
        'timed_out' => ['timed out'],
        'secure_setup' => ['ssl', 'tls', 'crypto', 'certificate', 'peer fingerprint'],
    ];

    /** The kind the transport's words say, the first kind whose words they contain. */
    public static function in(string $reported): self
    {
        $said = mb_strtolower($reported);

        foreach (self::WORDS as $kind => $words) {
            if (array_any($words, static fn(string $word): bool => str_contains($said, $word))) {
                return self::from($kind);
            }
        }

        return self::Other;
    }
}
