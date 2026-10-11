<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The kinds of scope a key is minted with: every read, reads and actions, or one member's own. */
enum HowAKeyIsScoped: string
{
    case Reading = 'read';

    case Acting = 'act';

    case AMembersOwn = 'member';
}
