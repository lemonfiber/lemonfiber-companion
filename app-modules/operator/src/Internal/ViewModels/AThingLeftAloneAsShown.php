<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One thing a declaration covers, as a row draws it: its name as the stack wrote it, and its kind. */
final readonly class AThingLeftAloneAsShown
{
    /**
     * @param string $name     the thing's name, as the stack wrote it
     * @param string $kindSaid the catalogue key for what kind of thing it is
     */
    public function __construct(
        public string $name,
        public string $kindSaid,
    ) {}
}
