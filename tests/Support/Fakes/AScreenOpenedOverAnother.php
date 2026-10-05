<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Design\Api\TakesTheThemeItOpensOver;
use Native\Mobile\Edge\NativeComponent;

/** A screen about no stack that is opened over another, as App settings is. */
final class AScreenOpenedOverAnother extends NativeComponent implements TakesTheThemeItOpensOver {}
