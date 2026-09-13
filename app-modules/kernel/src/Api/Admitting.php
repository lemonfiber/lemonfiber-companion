<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The one place a credential becomes a session.
 *
 * `N1-R7` has two clauses and the port shape carries the second. Exchanging a
 * credential is ordinary; **not retaining it for re-sending** is the part that
 * takes a design, and {@see Credential} does most of it by being spent on the
 * way out. What this adds is that the exchange happens in one place: a port
 * with one method is a call site a reviewer can find, and the alternative — a
 * client that can also knock — is a client holding a password.
 *
 * **It takes the stack rather than an address.** `N1-R11` keeps each stack's
 * session separate, and a port taking somewhere to dial would let a caller pair
 * a credential with the wrong machine. The stack also carries the fingerprint
 * `N1-R19` pins against, which is what makes this the one request that must
 * never reach an unverified peer — it is the one carrying the operator's
 * password.
 *
 * **There is no `resume()` and no `endSession()` beside it.** A session that has
 * ended is `N1-R44`'s screen and a session that is discarded is `N4`'s storage,
 * and neither is this. A port method nothing calls is a promise no adapter has
 * been held to; they arrive with the screens that need them.
 */
interface Admitting
{
    /**
     * Offer a credential, and come away with a session or with a reason.
     *
     * Answers {@see Admitted} rather than raising, which `C1` requires and
     * which is right for a second reason here: a stack that refused the
     * password, one that has stopped listening and one that could not be
     * reached are three ordinary states of the world, told apart by `N1-R10`.
     * A method returning a `Session` could report them only by throwing, which
     * makes the common case the one nothing checks.
     *
     * The credential is spent by being offered — that is {@see Credential}'s
     * doing rather than this port's, and it is why this takes one rather than a
     * string: a string can be offered twice.
     */
    public function admit(Stack $stack, Credential $said): Admitted;
}
