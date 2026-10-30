<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** An invitation the stack is still carrying out: the handle of its work, and the name it is about, which a refusal is drawn with. */
final readonly class AnInvitationFollowed
{
    public function __construct(
        public string $job,
        public string $name,
    ) {}
}
