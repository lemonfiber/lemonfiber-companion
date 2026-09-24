<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhoSetIt;

/**
 * One row's origin, as the two things a template needs to say it.
 *
 * A key naming which of the four arms this is, and the one string that arm
 * carries — a plugin's name, or the stack's reason an origin is unknown. The
 * two arms that name nothing carry `null`, not an empty string: a blank here
 * is never put on the screen, so nothing could tell one blank from another,
 * and the template reads the absence rather than interpolating a nothing.
 *
 * **It exists so the fold has somewhere to land.**
 * {@see \Modules\Kernel\Api\WhoPutItThere::whichever()} must be given a
 * closure per arm returning an object, and the alternative to a named type is
 * an anonymous one — which is a shape with no name for the analyser to check,
 * in an app whose every other reading has a type at each step.
 */
final readonly class WhereARowSaysItCameFrom
{
    public function __construct(
        public WhoSetIt $came,
        public ?string $attributed = null,
    ) {}

    /**
     * Whether this is the stack's own, which a report or a list of services
     * leaves unmarked.
     *
     * Nearly every check and every service is, and a word repeated on forty
     * rows is a word nobody reads — so those screens mark the others and say
     * once, under the list, that an unmarked row is the stack's own.
     */
    public function isTheStacksOwn(): bool
    {
        return $this->came === WhoSetIt::Bundled;
    }
}
