<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** Where changing one decision setup makes is offered. */
enum HowADecisionIsChanged
{
    case Here;

    case WhereItIsKept;

    case AtTheMachine;

    case NotListedWhereItIsKept;

    case ToObtain;
}
