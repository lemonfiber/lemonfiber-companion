<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhoSetIt;

/**
 * One setting's origin, as the two things a template needs to say it.
 *
 * A key naming which of the four arms this is, and the one string that arm
 * carries — a plugin's name, or the stack's reason an origin is unknown. Two
 * arms carry nothing and leave it empty.
 *
 * **It exists so the fold has somewhere to land.**
 * {@see \Modules\Kernel\Api\WhereASettingCameFrom::whichever()} must be given a
 * closure per arm returning an object, and the alternative to a named type is
 * an anonymous one — which is a shape with no name for the analyser to check,
 * in an app whose every other reading has a type at each step.
 */
final readonly class WhereARowSaysItCameFrom
{
    public function __construct(
        public WhoSetIt $came,
        public string $attributed = '',
    ) {}
}
