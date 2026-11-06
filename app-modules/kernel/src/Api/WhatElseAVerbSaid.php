<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a verb's report says beside the verb itself: lemonfiber's own guards it moved, the parts a start offers to install, and each service it wrote without the host's render devices, with why.
 */
final readonly class WhatElseAVerbSaid
{
    private function __construct(
        private WhatBecameOfTheMovesInPlace $guards,
        private TheInstallsAStartOffers $installs,
        private PluginLines $withoutRender,
        private string $whyNoRender,
    ) {}

    /** Nothing beside the verb. */
    public static function nothing(): self
    {
        return new self(WhatBecameOfTheMovesInPlace::none(), TheInstallsAStartOffers::these(), PluginLines::none(), '');
    }

    /** What the report says; `whyNoRender` is empty where the stack gave no reason. */
    public static function these(WhatBecameOfTheMovesInPlace $guards, TheInstallsAStartOffers $installs, PluginLines $withoutRender, string $whyNoRender): self
    {
        return new self($guards, $installs, $withoutRender, $whyNoRender);
    }

    /** Each of lemonfiber's own guards it moved onto its plugin, or on a rehearsal would, or could not. */
    public function guards(): WhatBecameOfTheMovesInPlace
    {
        return $this->guards;
    }

    /** The parts a start offers to install from this build. */
    public function installs(): TheInstallsAStartOffers
    {
        return $this->installs;
    }

    /** Each service taking `render` it wrote without the host's render devices, in the stack's words naming it. */
    public function withoutRender(): PluginLines
    {
        return $this->withoutRender;
    }

    /** Why the group owning the render devices was not asked or not kept, in the stack's words, or empty. */
    public function whyNoRender(): string
    {
        return $this->whyNoRender;
    }
}
