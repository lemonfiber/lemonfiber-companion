<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One line of what taking lemonfiber off reaches, as the row that draws it.
 */
final readonly class OneLineItReachesAsShown
{
    /**
     * @param string            $name             what it is called: a container, an image, or a full path
     * @param string            $sortSaid         the catalogue key for which sort of thing it is
     * @param string            $what             what it is, in the operator's words
     * @param bool              $holdsACredential whether removing it destroys a credential, whose value is never carried
     * @param ASizeAsShown|null $size             what it occupies, or nothing where the stack could not say
     * @param string            $whyKept          why the stack keeps it, or empty where it goes
     */
    public function __construct(
        public string $name,
        public string $sortSaid,
        public string $what,
        public bool $holdsACredential,
        public ?ASizeAsShown $size,
        public string $whyKept,
    ) {}
}
