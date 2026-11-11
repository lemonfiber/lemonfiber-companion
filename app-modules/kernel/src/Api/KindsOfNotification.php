<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;

use ArrayIterator;

use function in_array;

use IteratorAggregate;
use Traversable;

/**
 * Some kinds of notification, each once, in the order the kinds are declared.
 *
 * The kinds a stack raises as it is configured, which the notification page
 * offers a switch for and nothing else, or the kinds the operator switched off
 * for a stack.
 *
 * @implements IteratorAggregate<int, KindOfNotification>
 */
final readonly class KindsOfNotification implements IteratorAggregate
{
    /** @param list<KindOfNotification> $kinds */
    private function __construct(private array $kinds) {}

    /** These kinds, each once, in the order the kinds are declared. */
    public static function these(KindOfNotification ...$kinds): self
    {
        $these = [];

        foreach (KindOfNotification::cases() as $kind) {
            if (in_array($kind, $kinds, strict: true)) {
                $these[] = $kind;
            }
        }

        return new self($these);
    }

    /** Whether this kind is among them. */
    public function include(KindOfNotification $kind): bool
    {
        return in_array($kind, $this->kinds, strict: true);
    }

    /** @return Traversable<int, KindOfNotification> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->kinds);
    }

    /** The same, with this kind among them. */
    public function with(KindOfNotification $kind): self
    {
        return self::these($kind, ...$this->kinds);
    }

    /** The same, without this kind. */
    public function without(KindOfNotification $kind): self
    {
        return self::these(...array_filter($this->kinds, static fn(KindOfNotification $one): bool => $one !== $kind));
    }
}
