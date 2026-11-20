<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How a stack serves its web surface, as the run answering settled it when it started.
 */
final readonly class HowItIsServed
{
    private function __construct(
        private int $port,
        private bool $encrypted,
        private bool $network,
        private string $named,
        private string $unrecorded,
        private ?TheCertificateItPresents $certificate,
    ) {}

    /** Served plainly, with no certificate. */
    public static function plainly(int $port, bool $network, string $named, string $unrecorded): self
    {
        return new self($port, encrypted: false, network: $network, named: $named, unrecorded: $unrecorded, certificate: null);
    }

    /** Served encrypted, presenting that certificate. */
    public static function presenting(TheCertificateItPresents $certificate, int $port, bool $network, string $named, string $unrecorded): self
    {
        return new self($port, encrypted: true, network: $network, named: $named, unrecorded: $unrecorded, certificate: $certificate);
    }

    /** The port it answers on. */
    public function port(): int
    {
        return $this->port;
    }

    /** Whether it is encrypted. */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /** Whether it is offered to the network rather than to this machine alone. */
    public function isOnTheNetwork(): bool
    {
        return $this->network;
    }

    /** The name a request has to carry, or nothing where the stack names none. */
    public function named(): string
    {
        return $this->named;
    }

    /** Why where it is served could not be written down, in the stack's words, or nothing. */
    public function unrecorded(): string
    {
        return $this->unrecorded;
    }

    /**
     * Say what happens where it presents a certificate and where it is served plainly, and get back what you built.
     *
     * @template TPresents of object
     * @template TPlain of object
     *
     * @param Closure(TheCertificateItPresents): TPresents $presenting
     * @param Closure(): TPlain                          $plainly
     *
     * @return TPresents|TPlain
     */
    public function whatItPresents(Closure $presenting, Closure $plainly): object
    {
        return $this->certificate instanceof TheCertificateItPresents ? $presenting($this->certificate) : $plainly();
    }
}
