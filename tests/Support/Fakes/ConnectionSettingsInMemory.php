<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\SettingsKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

/**
 * `connection`'s settings, kept in memory, held by `SettingsKeptContractTest`
 * to what the database store promises: one value, the later replacing the
 * earlier, and nothing once forgotten.
 */
final class ConnectionSettingsInMemory implements SettingsKept
{
    private ?SealedPayload $payload = null;

    private ?Shape $shape = null;

    private function __construct(private readonly bool $reachable) {}

    /** A store that keeps what it is handed. */
    public static function empty(): self
    {
        return new self(reachable: true);
    }

    /** A store that cannot be written or read. */
    public static function unreachable(): self
    {
        return new self(reachable: false);
    }

    public function keep(SealedPayload $payload, Shape $shape, Instant $setAt): Noted
    {
        if (! $this->reachable) {
            return Noted::notKept();
        }

        $this->payload = $payload;
        $this->shape = $shape;

        return Noted::downAt($setAt);
    }

    public function kept(): KeptSettings
    {
        return $this->payload instanceof SealedPayload && $this->shape instanceof Shape
            ? KeptSettings::found($this->payload, $this->shape)
            : KeptSettings::none();
    }

    public function forgetEverything(): Forgotten
    {
        $had = $this->payload instanceof SealedPayload ? 1 : 0;
        $this->payload = null;
        $this->shape = null;

        return Forgotten::rows($had);
    }
}
