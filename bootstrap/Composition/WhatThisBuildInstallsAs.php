<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use function is_string;

use Modules\Kernel\Api\WhoThisAppIs;

use function trim;

/**
 * The check that a build is this application and not a second one.
 *
 * {@see WhoThisAppIs} declares the identity; this is what happens when a build
 * says something else.
 *
 * **Outside the modules on purpose.** `D2` and `D3` hold a module's `Api` to
 * value objects rather than primitives, and rightly: a string crossing a module
 * boundary is a string any caller could have swapped. What arrives here is not
 * a value of this application's at all — it is whatever an environment carried,
 * which may be anything and is most usefully described as `mixed`. Composing
 * the application is where that belongs. Refused rather than ignored, and that is the whole of
 * it: a configured identity silently dropped is the same failure as one
 * silently used, because in both cases somebody set a value believing it did
 * something.
 *
 * **Asked where the identity is decided, which is the config file.** A build
 * step runs in whatever environment somebody gave it and can differ from the
 * one the bundle is assembled in; this runs in the same expression that
 * produces the value, so there is no gap at all between what was checked and
 * what is used. It is also the one directory `env()` may be read in — outside
 * it a cached config answers null, which would make this check pass by reading
 * nothing.
 */
final readonly class WhatThisBuildInstallsAs
{
    /**
     * The identity this build installs as, or a refusal naming both.
     *
     * Answers the declared one either way, because that is the only identity
     * this application has: a build that names it agrees, and a build that
     * names something else does not get to install at all. Takes what the
     * environment said rather than reading it, so this class has no opinion
     * about where a configuration comes from and a test needs to arrange none.
     */
    public static function orRefuse(mixed $configured): string
    {
        $said = is_string($configured) ? trim($configured) : '';

        // Blank is *nothing configured*, which is the ordinary case and is not
        // a disagreement: a build that says nothing gets the declared identity.
        // Only a build that says something else is refused.
        if ($said === '' || $said === WhoThisAppIs::IDENTITY) {
            return WhoThisAppIs::IDENTITY;
        }

        throw ThisBuildIsSomebodyElse::ratherThan($said, WhoThisAppIs::IDENTITY);
    }
}
