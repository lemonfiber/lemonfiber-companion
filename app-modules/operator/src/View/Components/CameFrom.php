<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\WhereARowSaysItCameFrom;

use function view;

/**
 * Who put a row there, said in the sentence its screen chose.
 *
 * One component for every screen that attributes — a setting, a check, a
 * service — because the one decision in it is the same on all of them: an arm
 * that names somebody is said with the name, and an arm that names nobody is
 * said without it. Written once, so a screen gaining an attribution cannot
 * interpolate a nothing into *set by the  plugin*.
 *
 * The sentence is handed in as a key, off the case, because it is the one
 * thing that differs: a plugin *sets* a value and *brings* a service.
 */
final class CameFrom extends Component
{
    /** @param string $said the catalogue key for the sentence, derived from the case */
    public function __construct(
        public readonly WhereARowSaysItCameFrom $from,
        public readonly string $said,
    ) {}

    public function render(): View
    {
        return view('operator::components.came-from');
    }
}
