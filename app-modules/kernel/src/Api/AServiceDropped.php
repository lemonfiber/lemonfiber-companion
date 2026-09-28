<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * A service the stack used to carry, why it went, and what took its place where anything did.
 *
 * **Nothing having replaced it is an answer**, and the commonest one: most
 * things that go are not replaced. So a replacement is its own constructor
 * rather than a nullable argument, and {@see self::replacement()} makes a
 * caller say what happens for each.
 */
final readonly class AServiceDropped
{
    private function __construct(
        private ServiceId $service,
        private string $removedIn,
        private string $reason,
        private ?string $replacedBy = null,
    ) {}

    /** A service that went, in which version, and why; nothing named in its place. */
    public static function went(ServiceId $service, string $removedIn, string $reason): self
    {
        return new self($service, self::said('removed_in', $removedIn), self::said('reason', $reason));
    }

    /** A service that went, in which version, and why, and what took its place. */
    public static function replaced(ServiceId $service, string $removedIn, string $reason, string $by): self
    {
        return new self($service, self::said('removed_in', $removedIn), self::said('reason', $reason), self::said('replaced_by', $by));
    }

    /** The id it was declared under, which is the name an operator will look for. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** The stack version whose catalogue stopped carrying it. */
    public function removedIn(): string
    {
        return $this->removedIn;
    }

    /** Why it went. */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Say what took its place, or say that nothing did.
     *
     * @template TBy of object
     * @template TNothing of object
     *
     * @param  Closure(string): TBy  $by
     * @param  Closure(): TNothing   $nothing
     * @return TBy|TNothing
     */
    public function replacement(Closure $by, Closure $nothing): object
    {
        return $this->replacedBy === null ? $nothing() : $by($this->replacedBy);
    }

    /** One word, less the space around it, or the refusal naming which it was. */
    private static function said(string $field, string $word): string
    {
        $shown = trim($word);

        if ($shown === '') {
            throw CatalogueSaysNothing::about($field);
        }

        return $shown;
    }
}
