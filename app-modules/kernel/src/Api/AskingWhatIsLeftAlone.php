<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

interface AskingWhatIsLeftAlone
{
    public function leftAloneOn(Stack $stack, Session $session): WhatWasFoundOfTheAreasLeftAlone;
}
