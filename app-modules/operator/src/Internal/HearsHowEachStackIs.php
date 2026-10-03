<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Operator\Internal\ViewModels\WhatTheLaunchWas;
use Modules\Wayfinding\Api\HearingEachStack;
use Modules\Wayfinding\Api\WhatEachStackSaidSoFar;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Each stack's one line, heard on its own subscription while the list is open.
 *
 * The list's first frame is drawn from what was kept, with when it was heard.
 * The subscriptions open on the first wake after it, so nothing is reached
 * before that frame is up. How each stack is listened to is
 * {@see HearingEachStack}'s, which the list the top bar's name opens shares.
 *
 * **Nothing is reached while the lock stands**, nor on a launch that found no
 * network: the list is then drawing nothing a subscription could add to.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowEachStackIs
{
    /**
     * What each stack's subscription has said so far.
     *
     * `public` because it is what the component's property syncing writes.
     */
    public ?WhatEachStackSaidSoFar $heardFromEach = null;

    /**
     * Take what each subscription has delivered, or let go of them all.
     *
     * Opens a stack's subscription where it is not open and its break has been
     * waited out. Taking sends nothing to the stack.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function listen(): void
    {
        // Only a launch that found a stack to open on reaches one. A locked
        // launch, an unpaired one and one with no network did not.
        if ($this->howItOpened()->opensOn === '') {
            return;
        }

        $this->heardFromEach = $this->hearingEach()->after($this->heardFromEachSoFar(), ...$this->configured());
    }

    /**
     * Let go of every subscription whenever the list stops being the screen in front.
     *
     * A return opens them again at once.
     */
    public function stop(): void
    {
        $this->heardFromEach = $this->hearingEach()->letGo($this->heardFromEachSoFar());

        parent::stop();
    }

    abstract public function howItOpened(): WhatTheLaunchWas;

    abstract public function configured(): Configured;

    /** The ports the list listens with, handed over by the screen that holds them. */
    abstract protected function listensWith(): WhatItListensWith;

    private function heardFromEachSoFar(): WhatEachStackSaidSoFar
    {
        return $this->heardFromEach ??= WhatEachStackSaidSoFar::nothingYet();
    }

    private function hearingEach(): HearingEachStack
    {
        return new HearingEachStack($this->listensWith(), $this->storage);
    }
}
