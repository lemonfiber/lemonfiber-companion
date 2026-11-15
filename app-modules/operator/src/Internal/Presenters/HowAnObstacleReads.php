<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\TheObstaclesNamed;
use Modules\Operator\Internal\ViewModels\AnObstacleAsShown;

/**
 * What the core named in the way of a reading, as the template draws it.
 */
final readonly class HowAnObstacleReads
{
    /**
     * Each obstacle's sentence, remedy and code, in the core's order.
     *
     * @return list<AnObstacleAsShown>
     */
    public function all(TheObstaclesNamed $named): array
    {
        $shown = [];

        foreach ($named as $obstacle) {
            $shown[] = new AnObstacleAsShown(
                said: $obstacle->said()->shown(),
                remedy: $obstacle->remedy()->action(),
                code: $obstacle->code()->shown(),
            );
        }

        return $shown;
    }
}
