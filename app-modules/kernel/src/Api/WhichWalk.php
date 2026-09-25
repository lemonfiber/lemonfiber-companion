<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** Which walkthrough a stack is offered: the whole pipeline, or media already on disk. */
enum WhichWalk: string
{
    /** Search, grab, download, import, and see it in the library. */
    case Pipeline = 'pipeline';

    /** For a stack that acquires nothing: media already on disk, confirmed visible to the media server. */
    case LibraryOnly = 'library-only';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.walkthrough.shape.%s', $this->value);
    }
}
