<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/** Who chose a back-end: the operator, or a member of the household by name. */
final readonly class WhoChoseIt
{
    private function __construct(private string $member) {}

    public static function theOperator(): self
    {
        return new self('');
    }

    public static function aMember(string $named): self
    {
        if (trim($named) === '') {
            throw WhereAlertsGoSaysNothing::about('the member who chose a back-end');
        }

        return new self($named);
    }

    /**
     * @template TOperator of object
     * @template TMember of object
     *
     * @param  Closure(): TOperator       $operator
     * @param  Closure(string): TMember   $member
     * @return TOperator|TMember
     */
    public function either(Closure $operator, Closure $member): object
    {
        return $this->member === '' ? $operator() : $member($this->member);
    }
}
