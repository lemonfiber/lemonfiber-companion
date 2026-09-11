<?php

declare(strict_types=1);

/*
 * The screens, and the URIs the device asks for them by.
 *
 * `Route::native()` is a macro the package adds: it registers the component
 * with NativeRouter — which owns the navigation stack and keeps a live
 * instance per entry so state survives `back()` — and also registers an
 * ordinary GET route, because the device drives the application through the
 * HTTP kernel rather than through a render loop of its own.
 *
 * This file is loaded as the application's web routes. That is a slightly
 * misleading name for what it holds, so the file is named for what it is: a
 * declaration of screens. Nothing here is served to a browser, and nothing in
 * this application answers an HTTP request from the network.
 *
 * Screens are declared by their surface module's service provider where they
 * belong to a surface. This file carries only what the shell itself owns.
 */
