<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** The jobs a screen changing settings follows: a change to the settings store, and a change agreed to once the downloads land. */
enum ASettingChangeFollowed
{
    use TellsTheJobsApart;

    case InTheStore;
    case OnceLanded;
}
