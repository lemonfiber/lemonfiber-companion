<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One credential a stack holds, flattened for a template.
 *
 * There is no field for a value, because nothing upstream of this carries one.
 */
final readonly class ACredentialAsShown
{
    /**
     * @param string       $name       what it is, in the operator's words
     * @param string       $stateSaid  the catalogue key for where it stands
     * @param string       $originSaid the catalogue key for who produced it
     * @param list<string> $consumers  everything that authenticates with it, possibly nothing
     * @param string       $advisory   what is worth saying about it, or empty
     */
    public function __construct(
        public string $name,
        public string $stateSaid,
        public string $originSaid,
        public array $consumers,
        public string $advisory,
    ) {}
}
