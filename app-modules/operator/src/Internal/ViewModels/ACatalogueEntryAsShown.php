<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One plugin the stack's catalogue offers, as a row: what it is, what the household is without it, and whether it is installed here.
 *
 * The grants and credentials it asks for are not on it: they are the
 * rehearsal's, which choosing the row asks for.
 */
final readonly class ACatalogueEntryAsShown
{
    /**
     * @param string $id          the name it is installed by
     * @param string $name        what it calls itself
     * @param string $description what it is for
     * @param string $version     the version the catalogue offers
     * @param string $withoutIt   what the household is without it
     * @param string $installed   the version installed here, or empty where it is not installed
     * @param bool   $newer       whether the stack says the catalogue's version is newer than the one installed
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $version,
        public string $withoutIt,
        public string $installed,
        public bool $newer,
    ) {}
}
