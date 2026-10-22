<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\WhereAStreamResumes;

use function sprintf;

/** Where a stream resumes, as a word a test can compare. */
final readonly class WhereItResumes
{
    public static function said(WhereAStreamResumes $resumes): string
    {
        return $resumes->either(
            after: static fn(AnEventHeard $event): Code => Code::of(sprintf('after:%s', $event->shown())),
            fromTheStart: static fn(): Code => Code::of('from-the-start'),
        )->shown();
    }
}
