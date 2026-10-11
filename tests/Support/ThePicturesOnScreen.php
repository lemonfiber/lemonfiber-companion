<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Household\Internal\ThePicturesItDraws;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Tests\Support\Fakes\ArtworkHeldInMemory;
use Tests\Support\Fakes\AStackWithPosters;

/**
 * The pictures a screen under test draws its posters with.
 */
final class ThePicturesOnScreen
{
    /** Pictures from a stack that hands over no poster, so every poster stays lettered. */
    public static function none(): ThePicturesItDraws
    {
        return new ThePicturesItDraws(ArtworkHeldInMemory::working(), AStackWithPosters::answering(HowArtworkCame::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    }
}
