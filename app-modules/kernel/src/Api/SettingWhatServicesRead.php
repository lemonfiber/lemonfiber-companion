<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Reading every setting and secret a stack's services read, and changing a setting: rehearsed first, carried out on a yes, and followed.
 */
interface SettingWhatServicesRead
{
    /** Ask a stack for every setting and secret its services read, or come away with a reason. */
    public function readOn(Stack $stack, Session $session): WhatWasFoundOfTheSettings;

    /**
     * Ask what the change would do, writing nothing.
     *
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ASettingChange $change): WhatBecameOfTheWork;

    /**
     * Make the change, as the rehearsal agreed to described it.
     *
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ASettingChangeAgreed $agreed): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
