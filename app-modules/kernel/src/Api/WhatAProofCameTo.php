<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What asking one of a plugin's proofs came to, or that it was not asked.
 *
 * **Not asked is never passed, and could not conclude is never failed.** A
 * rehearsal asks nothing, so every proof on it is not asked; a proof the
 * service could not settle is unproven with the stack's reason; and a proof
 * failing only as its plugin declared it would says each place it does.
 */
final readonly class WhatAProofCameTo
{
    private function __construct(private WhatAProofSays $says, private PluginLines $said) {}

    /** It was not asked. */
    public static function notAsked(): self
    {
        return new self(WhatAProofSays::NotAsked, PluginLines::none());
    }

    /** It held. */
    public static function passed(): self
    {
        return new self(WhatAProofSays::Passed, PluginLines::none());
    }

    /** It did not hold, and each of these is a way it did not. */
    public static function failed(PluginLines $faults): self
    {
        return new self(WhatAProofSays::Failed, $faults);
    }

    /** Nothing could be concluded, and this is why; blank is refused. */
    public static function unproven(string $why): self
    {
        return new self(WhatAProofSays::Unproven, PluginLines::under('why', trim($why)));
    }

    /** It fails only where its plugin declared it would, for each of these reasons. */
    public static function failingAsDeclared(PluginLines $reasons): self
    {
        return new self(WhatAProofSays::FailingAsDeclared, $reasons);
    }

    /** Which of the five it is. */
    public function says(): WhatAProofSays
    {
        return $this->says;
    }

    /** What the stack said with it: each fault, the reason it was unproven, or each declared failure. */
    public function said(): PluginLines
    {
        return $this->said;
    }

    /** Whether it stops an install: only a proof asked and failed does. */
    public function stopsAnInstall(): bool
    {
        return $this->says === WhatAProofSays::Failed;
    }
}
