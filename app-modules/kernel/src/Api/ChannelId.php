<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** What the core calls one live TV channel a member may watch. */
final readonly class ChannelId
{
    private function __construct(private string $named) {}

    /** One channel, named as the core names it. */
    public static function called(string $channel): self
    {
        $named = trim($channel);

        if ($named === '') {
            throw ChannelIsUnnamed::whereOneWasExpected();
        }

        return new self($named);
    }

    /** The identifier, exactly as the core spelled it. */
    public function named(): string
    {
        return $this->named;
    }

    public function is(self $other): bool
    {
        return $this->named === $other->named;
    }
}
