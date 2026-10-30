<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The running release's notes, where they describe this copy. */
final readonly class TheReleaseNotesAsShown
{
    /**
     * @param string                        $release     the running release's version
     * @param string                        $noticedSaid the catalogue key for whether the household would notice it
     * @param bool                          $withdrawn   whether the running release was taken back
     * @param list<AGroupOfTheNotesAsShown> $groups      what it changed, group by group
     */
    public function __construct(
        public string $release,
        public string $noticedSaid,
        public bool $withdrawn,
        public array $groups,
    ) {}
}
