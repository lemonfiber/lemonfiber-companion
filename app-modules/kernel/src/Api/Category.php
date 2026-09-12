<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The family a check belongs to, so a run can be narrowed to one of them.
 *
 * The server's own list, and the whole of it. These are the diagnostic
 * categories the product recognises rather than the checks that fill them —
 * the checks arrive over time, so a category may name more than lemonfiber can
 * yet establish, and an empty one is a category with nothing to say rather
 * than a mistake.
 *
 * Written out here rather than parsed from the artefact because a capability
 * may not read a file and may not know the SDK exists. What keeps it in step
 * is the adapter that maps the wire into it, which cannot compile against a
 * case that is missing.
 *
 * No label. What a screen shows for a category is text a person reads, so it
 * comes from the translator against a key (L1) — a name written here would be
 * English on a Dutch phone.
 */
enum Category: string
{
    /** Docker present, the daemon reachable, the platform understood. */
    case Environment = 'environment';

    /** The data root: reachable, writable, one filesystem, room to grow. */
    case Storage = 'storage';

    /** Ports free, bindings matching policy, services reachable. */
    case Network = 'network';

    /** Torrent traffic genuinely leaves through the tunnel. */
    case Vpn = 'vpn';

    /** Each credential still valid. */
    case Credentials = 'credentials';

    /** Health, crash loops, version skew, the wiring between services. */
    case Services = 'services';

    /** Provider quota, subscription validity, indexer responsiveness. */
    case Providers = 'providers';

    /** Stuck items, repeated import failures, orphaned downloads. */
    case Queue = 'queue';

    /** Drift from lemonfiber-managed state, permissions, manifest validity. */
    case Config = 'config';
}
