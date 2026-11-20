<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;
use SensitiveParameter;

use function trim;

/**
 * The handle a stack answered with a sign-in code, which finishes that sign-in once the code is approved.
 *
 * Held in memory by the screen showing the code, inside a closure no dump,
 * export or trace prints, and refused by serialisation: never stored, logged
 * or put in an address.
 */
final readonly class AWaitingCode
{
    /** @var Closure(): string */
    private Closure $handle;

    private function __construct(#[SensitiveParameter] string $handle)
    {
        $this->handle = static fn(): string => $handle;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['handle' => '(a waiting code, hidden)'];
    }

    /** @return array<never> */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aWaitingCode();
    }

    /** @param array<mixed> $data */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aWaitingCode();
    }

    /** The handle as the stack answered it. */
    public static function answered(#[SensitiveParameter] string $handle): self
    {
        if (trim($handle) === '') {
            throw TheDoorSaysNothing::about('waiting');
        }

        return new self($handle);
    }

    /** The handle, for the request that finishes the sign-in. */
    public function forTheExchange(): string
    {
        return ($this->handle)();
    }
}
