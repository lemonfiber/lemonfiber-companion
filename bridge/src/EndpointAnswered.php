<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/** The endpoint a distributor gave a stack, with the public key the phone made for it, or the word for why there is none. */
final readonly class EndpointAnswered
{
    /**
     * @param string      $said the endpoint where one was given, and the bridge's word for why where none was
     * @param string|null $key  the public key as bytes, or none where no endpoint was given
     */
    private function __construct(private string $said, private ?string $key) {}

    /** Given: the endpoint, and the public key as bytes. */
    public static function given(string $endpoint, string $key): self
    {
        return new self($endpoint, $key);
    }

    /** None, for the bridge's word `$because`, or an empty one where it gave none. */
    public static function not(string $because): self
    {
        return new self($because, null);
    }

    /**
     * @template TGiven
     * @template TNot
     *
     * @param  Closure(string, string): TGiven $given the endpoint, and the public key as bytes
     * @param  Closure(string): TNot           $not   the bridge's word for why
     * @return TGiven|TNot
     */
    public function either(Closure $given, Closure $not): mixed
    {
        return $this->key === null ? $not($this->said) : $given($this->said, $this->key);
    }
}
