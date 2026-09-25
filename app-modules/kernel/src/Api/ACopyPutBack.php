<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What putting a copy back did, as the stack reported it.
 *
 * What it restored, which version of lemonfiber wrote the copy, and where the
 * data went — back where it came from, or somewhere else, which is the half an
 * operator has to be told.
 */
final readonly class ACopyPutBack
{
    private function __construct(
        private ScopeOfACopy $scope,
        private string $takenBy,
        private WhereTheDataGoes $data,
    ) {}

    /** The stack's report of one restore. */
    public static function reported(ScopeOfACopy $scope, string $takenBy, WhereTheDataGoes $data): self
    {
        if (trim($takenBy) === '') {
            throw KeepingSaysNothing::about('from_version');
        }

        return new self($scope, $takenBy, $data);
    }

    /** What it restored. */
    public function scope(): ScopeOfACopy
    {
        return $this->scope;
    }

    /** The version of lemonfiber that wrote the copy. */
    public function takenBy(): string
    {
        return $this->takenBy;
    }

    /** Where the data went. */
    public function whereTheDataWent(): WhereTheDataGoes
    {
        return $this->data;
    }
}
