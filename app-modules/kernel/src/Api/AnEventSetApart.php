<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One kind of event the operator set apart from the preset.
 *
 * Named by the name a finding gives it, carried exactly as the stack spelled
 * it: it is what the operator typed when they set it apart, and what a
 * terminal takes to change it back.
 */
final readonly class AnEventSetApart
{
    private function __construct(private string $kind, private WhetherItIsHeard $heard) {}

    /** One event kind, and whether it is heard about. */
    public static function of(string $kind, WhetherItIsHeard $heard): self
    {
        if (trim($kind) === '') {
            throw AlertSaysNothing::about('kind');
        }

        return new self($kind, $heard);
    }

    /** The kind of event, as a finding names it. */
    public function kind(): string
    {
        return $this->kind;
    }

    /** Whether it is heard about, whatever the preset would say. */
    public function heard(): WhetherItIsHeard
    {
        return $this->heard;
    }
}
