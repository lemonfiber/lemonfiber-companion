<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What putting one copy back would do, read before anything is overwritten.
 *
 * The stack's rehearsal of a restore: the copy's own account of itself, what
 * it covers and holds, which version of lemonfiber wrote it and when, whether
 * it goes back a version, and where its data would land. Nothing has changed
 * when this exists, and it is the only thing a restore can be agreed against.
 *
 * **The agreement names this listing and no other.** The stack words it from
 * everything an operator reads here, so a yes carrying it is a yes to exactly
 * this; a listing that has moved on since is refused by the stack rather than
 * carried out.
 */
final readonly class WhatPuttingItBackWouldDo
{
    private function __construct(
        private ACopy $copy,
        private string $agreement,
        private ScopeOfACopy $scope,
        private string $takenBy,
        private string $takenAt,
        private WhatACopyHolds $contents,
        private bool $older,
        private WhereTheDataGoes $data,
    ) {}

    /**
     * The stack's listing of one copy.
     *
     * The agreement, the version that wrote it and when are each required:
     * a listing that cannot be named cannot be agreed to, and a copy that
     * will not say what wrote it is one nobody can judge.
     */
    public static function listed(
        ACopy $copy,
        string $agreement,
        ScopeOfACopy $scope,
        string $takenBy,
        string $takenAt,
        WhatACopyHolds $contents,
        bool $older,
        WhereTheDataGoes $data,
    ): self {
        foreach (['agreement' => $agreement, 'product_version' => $takenBy, 'created_at' => $takenAt] as $field => $said) {
            if (trim($said) === '') {
                throw KeepingSaysNothing::about($field);
            }
        }

        return new self($copy, $agreement, $scope, $takenBy, $takenAt, $contents, $older, $data);
    }

    /** Which copy this is about. */
    public function copy(): ACopy
    {
        return $this->copy;
    }

    /** What this listing is called, so a yes can name it. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** What the copy covers. */
    public function scope(): ScopeOfACopy
    {
        return $this->scope;
    }

    /** The version of lemonfiber that wrote it. */
    public function takenBy(): string
    {
        return $this->takenBy;
    }

    /** When it was taken, as the stack stamped it. */
    public function takenAt(): string
    {
        return $this->takenAt;
    }

    /** What it holds, which is what putting it back would overwrite. */
    public function contents(): WhatACopyHolds
    {
        return $this->contents;
    }

    /** Whether it comes from an older major version, and may need more reconciling after. */
    public function isOlder(): bool
    {
        return $this->older;
    }

    /** Where its data would land. */
    public function whereTheDataGoes(): WhereTheDataGoes
    {
        return $this->data;
    }
}
