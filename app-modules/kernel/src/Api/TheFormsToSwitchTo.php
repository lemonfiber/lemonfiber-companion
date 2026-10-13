<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The forms the operator chose as the stack's active set; at least one.
 */
final readonly class TheFormsToSwitchTo
{
    private function __construct(private Forms $forms) {}

    /** These forms as the active set; none at all is refused. */
    public static function these(Form ...$forms): self
    {
        if ($forms === []) {
            throw SwitchNamesNoForm::atAll();
        }

        return new self(Forms::these(...$forms));
    }

    public function forms(): Forms
    {
        return $this->forms;
    }
}
