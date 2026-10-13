<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whom a change to what may be asked for is about: the whole household, or one member of it.
 */
final readonly class WhoAnAllowanceIsFor
{
    private function __construct(private ?SomebodyInTheHousehold $somebody) {}

    /** The household as a whole, which holds for everybody nobody chose otherwise for. */
    public static function everybody(): self
    {
        return new self(null);
    }

    /** One member, leaving the household's own choice where it is. */
    public static function somebody(SomebodyInTheHousehold $who): self
    {
        return new self($who);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TEverybody of object
     * @template TSomebody of object
     *
     * @param Closure(): TEverybody                       $everybody
     * @param Closure(SomebodyInTheHousehold): TSomebody  $somebody
     *
     * @return TEverybody|TSomebody
     */
    public function either(Closure $everybody, Closure $somebody): object
    {
        return $this->somebody instanceof SomebodyInTheHousehold ? $somebody($this->somebody) : $everybody();
    }
}
