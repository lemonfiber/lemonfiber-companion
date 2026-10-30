<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the support bundle asked for from this screen, flattened for a template.
 *
 * The bundle is empty until the stack has answered with one, and the refusal
 * is nothing unless the stack refused, so a template cannot draw either off a
 * bundle still being gathered.
 */
final readonly class HowTheBundleWent
{
    /**
     * @param WhereTheWorkStands $stands      where the work stands, and whether the stack could be asked at all
     * @param ?ARefusalAsShown  $refused   why the stack refused the bundle, in its words, and nothing unless it refused
     * @param bool              $isWritten whether the bundle exists on the machine, rather than being described
     * @param ?ABundleAsShown   $bundle    the bundle, once the stack answered with one
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public bool $isWritten,
        public ?ABundleAsShown $bundle,
    ) {}
}
