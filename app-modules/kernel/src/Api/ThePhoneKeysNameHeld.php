<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The name this phone's key on a stack is registered under, or none. */
final readonly class ThePhoneKeysNameHeld
{
    private function __construct(private ?APhoneKeysName $name) {}

    /** The phone keeps this name for its key on the stack. */
    public static function named(APhoneKeysName $name): self
    {
        return new self($name);
    }

    /** It keeps none: no key was registered from this phone, or it was let go of. */
    public static function none(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TNamed of object
     * @template TNone of object
     *
     * @param Closure(APhoneKeysName): TNamed $named
     * @param Closure(): TNone                $none
     *
     * @return TNamed|TNone
     */
    public function either(Closure $named, Closure $none): object
    {
        return $this->name instanceof APhoneKeysName ? $named($this->name) : $none();
    }
}
