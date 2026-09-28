<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * The stage a running walk is at, as a template draws it, including when none can be said.
 *
 * Three states, each told apart from the others and from a walk that is idle:
 * a stage heard and still current; a stage heard before the subscription broke,
 * said with when it was heard and never as where the walk is now; and no stage
 * at all, either because the stack has not said one yet or because it could not
 * be heard.
 */
final readonly class TheStageAsShown
{
    /**
     * @param string     $step      the stack's word for the stage, or empty where none was heard
     * @param string     $said      what the walk said it was doing there, or empty
     * @param string     $detail    what was particular about it, or empty where the stack had nothing particular to say
     * @param AgoAsShown $ago       when the stage was heard, or live where it is current
     * @param bool       $listening whether the subscription is open, so a missing stage is one not said yet rather than one that could not be heard
     */
    public function __construct(
        public string $step,
        public string $said,
        public string $detail,
        public AgoAsShown $ago,
        public bool $listening,
    ) {}
}
