<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\StackId;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen coming to the front, about one stack or about none.
 *
 * The theme a screen is drawn in turns on the stack in its route and nothing
 * else it holds, so this holds that alone.
 */
final class AScreenComingToTheFront extends NativeComponent
{
    private function __construct() {}

    /** A screen about one stack, as its route names it. */
    public static function about(StackId $stack): self
    {
        $screen = new self();
        $screen->setParams(['stack' => $stack->stored()]);

        return $screen;
    }

    /** A screen whose route names a stack by nothing but blanks. */
    public static function aboutABlank(): self
    {
        $screen = new self();
        $screen->setParams(['stack' => ' ']);

        return $screen;
    }

    /** A screen about no stack: the first run, pairing, the list of stacks. */
    public static function aboutNoStack(): self
    {
        return new self();
    }
}
