<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The way back from an update, where the stack named one.
 *
 * Flattening these into *undo* is refused, and the reason is that they
 * put back different things. A rollback returns the service to the version it
 * was on. A restore returns it to the snapshot taken before the run — which
 * carries the data with it, and is therefore a larger promise and a different
 * conversation.
 *
 * An app that offered *undo* would be promising whichever of the two it happens
 * to get. Where the stack named neither, there is no case here to hold it: the
 * absence is `null` at the call site, and nothing on this type can be mistaken
 * for a way back that does not exist.
 */
enum HowToUndoIt: string
{
    /** Put the previous version back. */
    case Rollback = 'rollback';
    /** Put back the snapshot taken before the run. */
    case Restore = 'restore';

    /**
     * What a screen says this is.
     *
     * Built from the case rather than listed against it, so a case added
     * without a line fails the rule that reads both.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('updates.undo.%s', $this->value);
    }

    /**
     * Whether taking this back brings the data with it.
     *
     * The difference worth asking about before agreeing, and the reason these
     * are two cases. A restore undoes more than the update did.
     */
    public function carriesTheDataWithIt(): bool
    {
        return $this === self::Restore;
    }
}
