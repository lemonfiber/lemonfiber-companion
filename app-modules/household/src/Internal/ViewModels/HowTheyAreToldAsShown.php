<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** The ways a member may be told that something they asked for has arrived, whether this phone is woken, and what stopped their last choice. */
final readonly class HowTheyAreToldAsShown
{
    /** @param list<AWayToBeToldAsShown> $ways */
    private function __construct(
        public array $ways,
        public string $phone,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /**
     * The ways the core offered, and the key for whether this phone is woken.
     *
     * @param list<AWayToBeToldAsShown> $ways
     */
    public static function offered(array $ways, string $phone): self
    {
        return new self($ways, $phone, null);
    }

    /** Nothing offered, or nothing the core would say, where the choice is not drawn. */
    public static function nothingOffered(): self
    {
        return new self([], '', null);
    }

    /** The same ways, with what stopped the choice just made. */
    public function stoppedBy(WhatStoppedAMember $stopped): self
    {
        return new self($this->ways, $this->phone, $stopped);
    }

    /** Whether there is a choice to draw. */
    public function isOffered(): bool
    {
        return $this->ways !== [];
    }

    /**
     * The ways a member is told on once the one named is pressed: added where they were not told on it, and taken away where they were.
     *
     * @return list<string>
     */
    public function afterPressing(string $id): array
    {
        $chosen = [];

        foreach ($this->ways as $way) {
            if ($way->receiving !== ($way->id === $id)) {
                $chosen[] = $way->id;
            }
        }

        return $chosen;
    }
}
