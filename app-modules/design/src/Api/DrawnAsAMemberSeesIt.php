<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * A screen about a stack that is drawn in the member's theme whoever's session opened it.
 *
 * The operator's preview of the member's side is opened with the operator's
 * session, and a screen about a stack is otherwise drawn in the theme of the
 * session the phone holds for it. A preview drawn in the operator's theme
 * would not be what a member sees, which is the whole of what it is for.
 */
interface DrawnAsAMemberSeesIt {}
