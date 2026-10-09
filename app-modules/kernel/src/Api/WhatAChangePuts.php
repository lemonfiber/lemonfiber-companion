<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** What one change an install makes puts at its path: a directory, a whole document, a key minted for the plugin, or a region of one. */
enum WhatAChangePuts: string
{
    case Directory = 'directory';
    case Document = 'document';
    case Key = 'key';
    case Region = 'region';

    /** What it puts, as a catalogue key. */
    public function saidOnTheScreen(): string
    {
        return sprintf('plugins.puts.%s', $this->value);
    }
}
