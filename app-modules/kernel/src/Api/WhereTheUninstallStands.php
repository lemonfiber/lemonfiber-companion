<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The word a stack answers where taking lemonfiber off got to on one run.
 *
 * The wire's four tags, each deciding which of {@see WhereTakingItOffGot}'s
 * constructors a reading answers with and which fields beside it are read.
 */
enum WhereTheUninstallStands: string
{
    /** Everything is listed with its size, and nothing has been removed. */
    case Surveyed = 'surveyed';

    /** The tier and the reading were agreed to, and this run changed nothing: the state a rehearsal ends in. */
    case Confirmed = 'confirmed';

    /** Everything the reading named as going is gone. */
    case Complete = 'complete';

    /** Some of it could not be removed. */
    case Partial = 'partial';
}
