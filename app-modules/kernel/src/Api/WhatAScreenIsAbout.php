<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One thing a screen under a stack is about, named in the path that opens it. */
interface WhatAScreenIsAbout
{
    /** The text the path names it by, before it is encoded. */
    public function namedInAPath(): string;
}
