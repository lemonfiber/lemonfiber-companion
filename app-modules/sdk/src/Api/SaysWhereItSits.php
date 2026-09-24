<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function sprintf;

/**
 * How every wire-field enum says where one of its names sits under another.
 *
 * One body for all of them, so a path is written the same way whichever enum
 * each half of it lives in.
 */
trait SaysWhereItSits
{
    /**
     * This field's name as a path, where it is read off another field's value.
     *
     * A refusal saying an envelope's `state` is unreadable is ambiguous in the
     * `doctor` envelope, which carries one on every verdict and none at the
     * top — so the message says `verdict.state` and the operator knows which
     * row to look at. Built here rather than written out, so that renaming a
     * case renames it in the sentence too.
     */
    public function under(NamesAWireField $parent): string
    {
        return sprintf('%s.%s', $parent->value, $this->value);
    }
}
