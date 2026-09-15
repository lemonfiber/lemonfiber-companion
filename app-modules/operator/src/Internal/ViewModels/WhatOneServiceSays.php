<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\ServiceId;

/**
 * One service a stack runs, flattened for a template to read.
 *
 * {@see WhatOneStalledItemSays}'s sibling for `N2-R7`, and it exists for the
 * same reason: {@see \Modules\Kernel\Api\Daemon} hands one of its facts over
 * through a closure and Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowAServiceReads} folds one once
 * per row.
 *
 * **The id and the name are both carried, and they are not the same thing.**
 * The name is what an operator recognises and the id is what a verb is asked
 * for by, and on a stack where somebody renamed a service they differ. A row
 * carrying only one of them would either offer a verb about a name lemonfiber
 * does not know, or put an identifier in front of somebody looking for *Sonarr*.
 *
 * **The id stays a {@see ServiceId} and is not flattened to text.** Everything
 * else here is a string because a template renders it, but this one is also
 * handed back to {@see \Modules\Operator\Internal\WhereAStackIs::logsOf()} —
 * and a template that had to build the value itself would be a template naming
 * a kernel class, which is the module boundary in the one file nothing
 * analyses as code.
 *
 * `Internal` because it is a detail of how one surface reads a value; `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
final readonly class WhatOneServiceSays
{
    /**
     * @param ServiceId    $id          what a verb is asked for by, and what its logs are read for
     * @param string       $name        what the operator recognises it as
     * @param string       $form        which form it belongs to
     * @param string       $runsSaid    the key for how it is running
     * @param string       $mattersSaid the key for how much it matters
     * @param bool         $isSettling  whether it becomes something else by itself
     * @param bool         $isOurs      whether this stack is the one that runs it
     * @param bool         $wouldNotHelp whether restarting it now would make things worse
     * @param list<string> $leaning     the services that will not work without it
     * @param string       $exited      what it exited with, or empty where it did not
     */
    public function __construct(
        public ServiceId $id,
        public string $name,
        public string $form,
        public string $runsSaid,
        public string $mattersSaid,
        public bool $isSettling,
        public bool $isOurs,
        public bool $wouldNotHelp,
        public array $leaning,
        public string $exited,
    ) {}

    /**
     * Whether this service is the one a verb was agreed about.
     *
     * Asked of the row rather than compared in a template, so the comparison is
     * on the id and cannot quietly become one on the name — two services with
     * the same name is a stack somebody misconfigured, and a screen that
     * highlighted both would be the least of it.
     */
    public function is(ServiceId $other): bool
    {
        return $this->id->named() === $other->named();
    }
}
