<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The operator's yes to changing one setting, quoting the rehearsal that said what the change would do.
 */
final readonly class ASettingChangeAgreed
{
    private function __construct(private ASettingChange $change, private AnOffer $offer) {}

    /** Agreed against that rehearsal of this change; anything else is refused. */
    public static function after(TheSettingAsChanged $rehearsal, ASettingChange $change): self
    {
        if (! $rehearsal->rehearses($change)) {
            throw ASettingChangeWasNotRehearsed::before($change->how());
        }

        return new self($change, $rehearsal->offer());
    }

    /** The change agreed to. */
    public function change(): ASettingChange
    {
        return $this->change;
    }

    /** The name of the rehearsal agreed to. */
    public function offer(): AnOffer
    {
        return $this->offer;
    }
}
