<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the operating system installs this application as (`N1-R52`).
 *
 * An application's identity is not a label. Two builds carrying two identities
 * are two applications: they install beside one another, each with its own
 * storage, and neither can read the other's. The pairing and the pinned
 * fingerprint `N1-R45` refuses to discard live in that storage — so a build
 * arriving under a different identity does not update the operator's app, it
 * appears next to it, empty, while the pairing stays in the one now orphaned.
 * The operator is told to pair again and is given no reason, because from
 * inside either app nothing is wrong.
 *
 * **So it is declared here and not read from the environment.** It was
 * `env('NATIVEPHP_APP_ID')` with no default, which put it in a file that is not
 * in the repository — the environment of whoever ran the build, which is the
 * one thing `N1-R52` names. Two people building the same release produced two
 * applications, and a build where nobody had set it produced one with no
 * identity at all.
 *
 * It is also the Purpose's rule read one layer down. Which person the app
 * serves is decided by the credential that signs in, never by which build was
 * installed; an identity taken from the builder's environment makes *which
 * build was installed* a distinguishing fact about the app, which is the thing
 * that sentence forbids being load-bearing.
 */
final readonly class WhoThisAppIs
{
    /**
     * The one identity every build of this application carries.
     *
     * Reverse domain form, which is what both platforms expect, and `app.` on
     * the front rather than `com.` because this is not a company's product.
     */
    public const string IDENTITY = 'app.lemonfiber.companion';

    /** What the environment may name it, where it names it at all (`N1-R53`). */
    public const string CONFIGURED_AS = 'NATIVEPHP_APP_ID';
}
