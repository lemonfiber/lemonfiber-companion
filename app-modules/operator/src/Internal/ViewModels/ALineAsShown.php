<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One line of where the room went, flattened for a template.
 *
 * `$unshared` is null where nothing is shared, so the template shows one
 * figure there and two only where they differ.
 */
final readonly class ALineAsShown
{
    /**
     * @param string $aboutSaid the catalogue key for what the line is about
     * @param string $tree      the tree's name, or empty where the line is not about one
     * @param string $costsSaid the catalogue key for what getting it back would cost
     */
    public function __construct(
        public string $aboutSaid,
        public string $tree,
        public ASizeAsShown $occupies,
        public ?ASizeAsShown $unshared,
        public string $costsSaid,
    ) {}
}
