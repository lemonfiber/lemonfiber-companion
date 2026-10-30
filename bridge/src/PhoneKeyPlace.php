<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/** Where this phone could keep its key, in the device's word, or why it could not. */
final readonly class PhoneKeyPlace
{
    /** @param string|WhyNoPhoneKey $place where it could live, or why it could not */
    private function __construct(private string|WhyNoPhoneKey $place) {}

    /** It could, there. */
    public static function lives(string $where): self
    {
        return new self($where);
    }

    /** It could not, for this reason. */
    public static function not(WhyNoPhoneKey $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template T
     *
     * @param Closure(string): T        $lives
     * @param Closure(WhyNoPhoneKey): T $not
     *
     * @return T
     */
    public function either(Closure $lives, Closure $not): mixed
    {
        return $this->place instanceof WhyNoPhoneKey ? $not($this->place) : $lives($this->place);
    }
}
