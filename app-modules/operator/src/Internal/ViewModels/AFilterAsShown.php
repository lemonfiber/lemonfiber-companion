<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One chip that narrows What's new, by kind or by stack. */
final readonly class AFilterAsShown
{
    /**
     * @param string|null $said   the catalogue key for the chip's words, or null where `name` is them
     * @param string|null $name   a stack's name, where the chip is a stack's, or null
     * @param string $tap    what choosing it calls
     * @param bool   $chosen whether it is the one in force now
     */
    public function __construct(
        public ?string $said,
        public ?string $name,
        public string $tap,
        public bool $chosen,
    ) {}
}
