<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Design\Api\DrawnAsAMemberSeesIt;
use Modules\Kernel\Api\StackId;
use Native\Mobile\Edge\NativeComponent;

/** A screen about a stack that is drawn as a member sees it, as the operator's preview is. */
final class AScreenAMemberWouldSee extends NativeComponent implements DrawnAsAMemberSeesIt
{
    private function __construct() {}

    /** The screen, about the stack named. */
    public static function about(StackId $stack): self
    {
        $screen = new self();
        $screen->setParams(['stack' => $stack->stored()]);

        return $screen;
    }
}
