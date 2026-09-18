<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How a stack is dialled, as the two ways there are.
 *
 * Written as a type rather than as `=== 'https'` at the one place that asks,
 * because the string is a closed vocabulary and the comparison is a decision.
 * A literal spells the decision again at every reader, and the readers drift:
 * one lowercases first and one does not, one remembers `https` and one also
 * remembers the scheme somebody added for a local stack. The enum is the only
 * place either question is answered.
 *
 * **Anything else is refused rather than carried as unencrypted.** A scheme
 * this does not name is not an insecure address — it is pairing material that
 * did not survive the trip, and `file://` or `ftp://` reaching `isEncrypted()`
 * would be answered `false`, which reads as a true statement about a stack that
 * was never there. The app states whether the connection is
 * private; it does not ask it to state that about something it cannot dial.
 *
 * Plain `Http` is named here on purpose, and accepting it is the subject of the
 * note on {@see Address}: a stack on a local network is genuinely reached that
 * way, and refusing it would tell an operator their pairing code is broken when
 * what is true is that their connection is not private.
 */
enum Scheme: string
{
    /** Dialled in the clear. Legitimate on a local network, and never private. */
    case Http = 'http';

    /** Dialled over TLS. */
    case Https = 'https';

    /**
     * Whether what travels over this scheme is encrypted.
     *
     * The one fact it turns on, stated once. A screen that worked it out
     * from the scheme would be a second implementation of a one-line rule, and
     * the two would disagree about the case nobody thought of.
     */
    public function isEncrypted(): bool
    {
        return $this === self::Https;
    }
}
