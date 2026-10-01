<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why the stacks this device holds cannot be listed this launch, though they are still held.
 *
 * Two records that are there and cannot be read: one the store would not open,
 * and one written by a newer build of this app in a shape this one does not
 * read. Neither is a device paired with nothing, and reading either as one is
 * how the next pairing comes to write an empty record over the stacks that were
 * there. So the record is left alone, and the app says why it is
 * holding back rather than offering to start again.
 *
 * **The value is the stem** in {@see InTheConnectionCatalogue}: what happened
 * and what to do about it are the pair of lines it names.
 */
enum WhyTheStacksAreHeldBack: string
{
    /** The store holding the stacks would not open, so nothing in it could be read. */
    case TheStoreWouldNotOpen = 'store_unreadable';

    /** A newer build of this app wrote the record, in a shape this build does not read. */
    case ANewerAppWroteThem = 'store_newer';

    /** The key for what happened. */
    public function said(): string
    {
        return InTheConnectionCatalogue::under($this->value)->said();
    }

    /** The key for what to do about it. */
    public function remedy(): string
    {
        return InTheConnectionCatalogue::under($this->value)->remedy();
    }
}
