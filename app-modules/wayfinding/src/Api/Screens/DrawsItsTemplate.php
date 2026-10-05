<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * A screen that draws its template and hands it nothing.
 *
 * The screen names the template in its `TEMPLATE` constant, which is the
 * coupling, stated here because a trait cannot declare it. Read as a constant,
 * so the analyser still holds every name to a template that exists.
 *
 * @phpstan-require-extends NativeComponent
 */
trait DrawsItsTemplate
{
    public function render(): View
    {
        return view(self::TEMPLATE);
    }
}
