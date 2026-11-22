<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** The three answers the `plugin-catalogue` envelope's `state` gives, each a normal answer. */
enum HowThePluginCatalogueStands: string
{
    /** The index was fetched and verified, and its entries are listed. */
    case Listed = 'listed';

    /** Reaching the catalogue is switched off, and nothing was asked of it. */
    case Off = 'off';

    /** The index could not be fetched, verified or read. */
    case Failed = 'failed';
}
