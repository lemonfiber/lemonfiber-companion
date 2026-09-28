<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Links
    |--------------------------------------------------------------------------
    |
    | None. The framework's default links `public/storage` to the public disk
    | so a web server can hand out uploaded files, and this app serves no
    | files to anybody. NativePHP runs `storage:link` on every launch, and
    | on Android the default link's target is a directory the bundle does not
    | have, so the command failed and Laravel compiled its exception page to
    | report it — before the first frame, each time.
    |
    */

    'links' => [],

];
