<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Whose a setting or a secret is: the core's, or a namespace's.
 */
final readonly class WhoseASettingIs
{
    private function __construct(private string $namespace) {}

    /** The core's own. */
    public static function theCores(): self
    {
        return new self('');
    }

    /** A plugin's or a stack service's, by its namespace; a blank one is refused. */
    public static function theNamespaces(string $namespace): self
    {
        if (trim($namespace) === '') {
            throw SettingSaysNothing::about('namespace');
        }

        return new self(trim($namespace));
    }

    /** Whether this is the same owner as that one. */
    public function is(self $other): bool
    {
        return $this->namespace === $other->namespace;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(): T       $core
     * @param Closure(string): T $namespace
     *
     * @return T
     */
    public function either(Closure $core, Closure $namespace): object
    {
        return $this->namespace === '' ? $core() : $namespace($this->namespace);
    }
}
