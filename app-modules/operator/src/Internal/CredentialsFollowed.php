<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** The two jobs the credentials screen follows: ending a grant, and replacing the media server's internal certificate. */
enum CredentialsFollowed
{
    use TellsTheJobsApart;

    case TheRevoke;
    case TheReplacement;
}
