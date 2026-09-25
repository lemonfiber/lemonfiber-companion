<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A copy an operator asked for: of the whole stack, or of one service.
 *
 * The two scopes a copy can be asked for in. The third a copy can have, an
 * existing setup, is taken by the stack before it takes one over and is not
 * something anybody asks for, so there is no way to spell it here.
 */
final readonly class ACopyAsked
{
    private function __construct(private ?ServiceId $service) {}

    /** Every service, lemonfiber's own configuration, and the stack. */
    public static function ofTheWholeStack(): self
    {
        return new self(null);
    }

    /** One service's configuration alone. */
    public static function ofOneService(ServiceId $service): self
    {
        return new self($service);
    }

    /** What the copy would cover, stated the way a finished copy states it. */
    public function scope(): ScopeOfACopy
    {
        return $this->service instanceof ServiceId
            ? ScopeOfACopy::oneService($this->service)
            : ScopeOfACopy::theWholeStack();
    }

    /** The service the copy is narrowed to, or none where it covers the whole stack. */
    public function narrowedTo(): Services
    {
        return $this->service instanceof ServiceId ? Services::these($this->service) : Services::none();
    }
}
