<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Which of the five things a held subscription can answer, before what it carried. */
enum WhatArrived
{
    case Nothing;

    case ASignOfLife;

    case ASummary;

    case TheEnd;

    case AnObstacle;
}
