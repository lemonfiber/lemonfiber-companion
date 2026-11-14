<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/** How one delivery ended: confirmed by the back-end, sent with no confirmation, or failed for its reason. */
final readonly class HowADeliveryEnded
{
    private function __construct(private bool $confirmed, private ?string $failed) {}

    public static function confirmed(): self
    {
        return new self(confirmed: true, failed: null);
    }

    public static function sent(): self
    {
        return new self(confirmed: false, failed: null);
    }

    public static function failed(string $reason): self
    {
        if (trim($reason) === '') {
            throw WhereAlertsGoSaysNothing::about('why a delivery failed');
        }

        return new self(confirmed: false, failed: $reason);
    }

    /**
     * @template TConfirmed of object
     * @template TSent of object
     * @template TFailed of object
     *
     * @param  Closure(): TConfirmed      $confirmed
     * @param  Closure(): TSent           $sent
     * @param  Closure(string): TFailed   $failed
     * @return TConfirmed|TSent|TFailed
     */
    public function either(Closure $confirmed, Closure $sent, Closure $failed): object
    {
        if ($this->confirmed) {
            return $confirmed();
        }

        return $this->failed === null ? $sent() : $failed($this->failed);
    }
}
