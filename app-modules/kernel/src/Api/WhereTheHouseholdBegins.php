<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * The service the household begins at, what it is to them and where it is reached, or nowhere.
 *
 * Nowhere is a stack that publishes nothing somebody could begin at, and it
 * carries no service, no facing and no address.
 */
final readonly class WhereTheHouseholdBegins
{
    private function __construct(private string $service, private ?WhatItFaces $facing, private AnAddressToHand $address) {}

    /** The service they begin at, by the name it shows itself under; a blank one is refused. */
    public static function at(string $service, WhatItFaces $facing, AnAddressToHand $address): self
    {
        if (trim($service) === '') {
            throw TheDoorSaysNothing::about('service');
        }

        return new self($service, $facing, $address);
    }

    /** Nothing here is somewhere they could begin. */
    public static function nowhere(): self
    {
        return new self('', null, AnAddressToHand::none());
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAt of object
     * @template TNowhere of object
     *
     * @param Closure(string, WhatItFaces, AnAddressToHand): TAt $at
     * @param Closure(): TNowhere                               $nowhere
     *
     * @return TAt|TNowhere
     */
    public function either(Closure $at, Closure $nowhere): object
    {
        return $this->facing instanceof WhatItFaces
            ? $at($this->service, $this->facing, $this->address)
            : $nowhere();
    }
}
