<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use stdClass;

/**
 * A way to reach a stack that reaches nothing.
 *
 * What every test needing a client will hand its subject, which is why the
 * contract suite drives this and {@see \Modules\Sdk\Api\PinnedClients} through
 * the same expectations: a fake easier to satisfy than the adapter is a fake
 * that quietly widens what the rest of the suite is written against.
 *
 * **It answers a different object per call, because the adapter must.** A
 * client holds one stack's pin, so a fake that answered a shared instance would
 * let a test pass against an adapter that attributed one stack's reading to
 * another — `N1-R11`'s last clause, undetected.
 *
 * Written by hand rather than mocked (`G1`), so a change to the port fails to
 * compile here instead of drifting.
 */
final class AClientForWhicheverStack implements Reaching
{
    public function client(Stack $stack, Session $session): stdClass
    {
        // `stdClass` rather than a stub of the SDK's client: what the port
        // promises is *an object built for this stack*, and nothing in the
        // contract is about what the SDK can do. A stub carrying the SDK's
        // methods would invite a test to assert on them here, where no adapter
        // can be held to them.
        return new stdClass();
    }
}
