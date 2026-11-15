<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhatALineIsAbout;

/**
 * One category of the room the stack offers as costing nothing, flattened for a template.
 */
final readonly class ACategoryAsShown
{
    /**
     * @param WhatALineIsAbout $about     the category, which giving it back opens on
     * @param string           $aboutSaid the catalogue key for what the category is
     * @param int<0, max>      $files     how many files it holds
     */
    public function __construct(
        public WhatALineIsAbout $about,
        public string $aboutSaid,
        public ASizeAsShown $size,
        public int $files,
    ) {}
}
