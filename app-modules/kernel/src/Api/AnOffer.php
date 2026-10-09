<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * The name a stack gave what it showed the operator before they agreed, or none.
 *
 * Carried back with the yes so the stack can refuse a yes given for something
 * that has since moved, rather than spend it on what it would do now. Opaque:
 * nothing here reads it, and no decision is made from it.
 *
 * None is a stack that named nothing, as one that answers no offer does; a
 * yes carrying none acts as it does without one.
 */
final readonly class AnOffer
{
    private function __construct(private string $named) {}

    /** What the stack named it; a blank name is none. */
    public static function named(string $named): self
    {
        return new self(trim($named));
    }

    /** No name: the stack offered nothing it named. */
    public static function none(): self
    {
        return new self('');
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TNamed of object
     * @template TNone of object
     *
     * @param  Closure(string): TNamed  $named
     * @param  Closure(): TNone  $none
     * @return TNamed|TNone
     */
    public function either(Closure $named, Closure $none): object
    {
        return $this->named === '' ? $none() : $named($this->named);
    }
}
