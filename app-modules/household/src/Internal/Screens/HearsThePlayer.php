<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

/** A screen that shows Play, and looks again when the player moves. */
interface HearsThePlayer
{
    /** The player moved: look again at how the last title played from here stopped. */
    public function playerMoved(): void;
}
