<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `quality` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum QualityField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The presets in force, the overall choice first. */
    case Choices = 'choices';

    /** The resolution and encode a preset aims for. */
    case Resolution = 'resolution';

    /** What playing a preset costs, in plain terms. */
    case Transcoding = 'transcoding';

    /** Whether this machine would have to transcode a preset in software. */
    case NeedsTranscodingHere = 'needs_transcoding_here';

    /** The format chosen for music, where one is set. */
    case Music = 'music';

    /** Whether the quality configuration was edited by hand since the stack wrote it. */
    case Customised = 'customised';
}
