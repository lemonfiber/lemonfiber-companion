<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Modules\Kernel\Api\WhoThisAppIs;
use RuntimeException;

use function sprintf;

/**
 * A build was configured to install as an application that is not this one.
 *
 * Refused at the moment it is read rather than carried to a caller, because
 * there is no caller that could do anything sensible with it: an application
 * under the wrong identity is a second application, and every screen after this
 * point would be operating on storage the operator's own app cannot see.
 *
 * Both names are in the sentence. A refusal saying only *that is wrong* leaves
 * whoever hits it opening two files to find out what it disagreed with, and the
 * likeliest reader is somebody on a laptop at the end of a build.
 */
final class ThisBuildIsSomebodyElse extends RuntimeException
{
    public static function ratherThan(string $configured, string $declared): self
    {
        return new self(sprintf(
            'This build installs as %s and this application is %s. Two identities are two applications, each with its own storage, and the operator\'s pairing stays in the one now orphaned. Unset %s or set it to the second name.',
            $configured,
            $declared,
            WhoThisAppIs::CONFIGURED_AS,
        ));
    }
}
