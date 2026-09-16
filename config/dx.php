<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Stand-ins
    |--------------------------------------------------------------------------
    |
    | Whether `modules/dx` takes the place of the ports it can stand in for, so
    | the whole application can be run and looked at with no lemonfiber on the
    | network and nothing paired.
    |
    | Off unless something says otherwise, and that default is the important
    | half. `modules/dx` is a development dependency, so it is installed while
    | the suite runs and its provider is discovered along with every other —
    | which means a provider that bound unconditionally would quietly point the
    | entire suite at a stand-in, and every test would go on passing against
    | payloads nobody's stack ever sent. `Modules\Dx\Api\StandsIn` is worth
    | reading on why that is the failure this module most has to avoid being.
    |
    | Read here rather than in the provider, because `env()` outside a config
    | file is read once and then frozen by a cached config — and because `A9`
    | refuses it in a provider for the same reason it refuses every other read
    | before the first frame.
    |
    | Coerced rather than taken as it arrives, because the ways this value can
    | reach the application disagree about what it looks like. A `.env` line
    | gives the string `true`, a shell export gives `1`, and phpunit.xml gives
    | something stranger than either: it reads `value="false"` as a boolean and
    | then stringifies it to set the variable, so the process sees an empty
    | string. `Config::boolean()` is strict and right to be, so the conversion
    | belongs here, where every one of those spellings arrives.
    |
    | A release never reaches this line. `modules/dx` is absent from a release
    | install, so its provider is not discovered and there is nothing to switch
    | on; `N1-R61` rests on that absence rather than on this value being false.
    |
    */

    'stands_in' => filter_var(env('DX_STANDS_IN', default: false), FILTER_VALIDATE_BOOL),

];
