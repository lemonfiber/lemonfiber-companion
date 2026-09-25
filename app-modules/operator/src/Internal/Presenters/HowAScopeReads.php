<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;

/**
 * How much a copy covers, as the phrase every sentence about a copy is built around.
 *
 * One place, so a copy about to be taken, one being taken, one taken and one
 * put back all name their scope in the same words.
 */
final readonly class HowAScopeReads
{
    public function of(ScopeOfACopy $scope): AScopeAsShown
    {
        return $scope->either(
            wholeStack: static fn(): AScopeAsShown => new AScopeAsShown(
                said: 'stacks.copy.scope.whole_stack',
                with: [],
                trees: [],
            ),
            oneService: static fn(ServiceId $service): AScopeAsShown => new AScopeAsShown(
                said: 'stacks.copy.scope.service',
                with: ['name' => $service->named()],
                trees: [],
            ),
            existing: static fn(AnExistingSetup $setup): AScopeAsShown => new AScopeAsShown(
                said: 'stacks.copy.scope.existing',
                with: ['name' => $setup->project()],
                trees: [...$setup->trees()],
            ),
        );
    }
}
