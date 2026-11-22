<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

enum PluginCatalogueField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What one plugin the catalogue offers is for. */
    case Description = 'description';

    /** Why the catalogue was not read: the code and summary an install by name would be refused with. */
    case Failure = 'failure';

    /** Whether the stack says the catalogue's version is newer than the one installed. */
    case Newer = 'newer';

    /** When the verified index was published, in whole seconds since the epoch. */
    case Published = 'published';

    /** The verified index's serial. */
    case Serial = 'serial';

    /** How many entries the index holds that the stack could not list. */
    case Unreadable = 'unreadable';
}
