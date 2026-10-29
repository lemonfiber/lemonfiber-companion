<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One adapter that would write the files of the service it fronts, flattened for a template. */
final readonly class AnUpstreamWrittenAsShown
{
    /**
     * @param string $adapter  the adapter
     * @param string $upstream the service whose files it writes
     * @param string $at       where that service's configuration directory lands inside the adapter
     */
    public function __construct(
        public string $adapter,
        public string $upstream,
        public string $at,
    ) {}
}
