<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** The framework, and the primitives only an adapter may reach for. */
final readonly class Framework
{
    /** @return list<Fixture> */
    public static function frameworkCoupling(): array
    {
        return [
            Fixture::suite('A1', 'app-modules/health/src/Fixtures/UsesEloquent.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use Illuminate\Database\Eloquent\Model;

                final class UsesEloquent extends Model {}
                PHP, 'A1 — no Illuminate\\Database\\Eloquent', 'Modules\\Health'),

            Fixture::analyser('A2', 'Plain/UsesFacade.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Support\Facades\Cache;

                final class UsesFacade
                {
                    public function driver(): string
                    {
                        return Cache::getDefaultDriver();
                    }
                }
                PHP, 'A2 —'),

            Fixture::analyser('A3', 'Plain/LocatesService.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class LocatesService
                {
                    public function reach(): mixed
                    {
                        return app('something');
                    }
                }
                PHP, 'A3/A4'),

            Fixture::analyser('A4', 'Plain/ReadsConfig.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsConfig
                {
                    public function name(): mixed
                    {
                        return config('app.name');
                    }
                }
                PHP, 'A3/A4'),

            Fixture::suite('A5', 'app-modules/health/src/Fixtures/ReadsEnvironment.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class ReadsEnvironment
                {
                    public function debug(): mixed
                    {
                        return env('APP_DEBUG');
                    }
                }
                PHP, 'A5 — configuration is read from config'),

            Fixture::suite('A6', 'app-modules/health/src/Fixtures/HoldsStatic.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final class HoldsStatic
                {
                    public static string $cached = 'survives a dispatch';
                }
                PHP, 'A6/I1'),

            Fixture::suite('I1', 'app-modules/health/src/Fixtures/HoldsAnotherStatic.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final class HoldsAnotherStatic
                {
                    public static int $count = 0;
                }
                PHP, 'A6/I1'),

            // The other declaration, and its own fixture rather than a second
            // assertion on the two above: a static inside a method body has no
            // property for reflection to find and survives a dispatch just as
            // completely, so a fixture proving the property half says nothing
            // at all about this one.
            Fixture::suite('A6', 'app-modules/health/src/Fixtures/HoldsBetweenCalls.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                final readonly class HoldsBetweenCalls
                {
                    public function soFar(): int
                    {
                        static $seen = 0;

                        return ++$seen;
                    }
                }
                PHP, 'A6/I1'),

            Fixture::suite('A7', 'app-modules/health/src/Fixtures/UsesIlluminate.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Fixtures;

                use Illuminate\Support\Collection;

                final readonly class UsesIlluminate
                {
                    public function holds(): Collection
                    {
                        return new Collection();
                    }
                }
                PHP, 'A7/E4 — health', 'Illuminate'),

            Fixture::analyser('A8', 'Plain/UsesPlatformFacade.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Native\Mobile\Facades\SecureStorage;

                final class UsesPlatformFacade
                {
                    public function token(): mixed
                    {
                        return SecureStorage::get('session');
                    }
                }
                PHP, 'A8 —'),

            Fixture::analyser('A9', 'Plain/WorkingProvider.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Support\ServiceProvider;

                final class WorkingProvider extends ServiceProvider
                {
                    public function boot(): void
                    {
                        $warm = $this->app->make('something');
                    }
                }
                PHP, 'A9 —'),
        ];
    }

    /** @return list<Fixture> */
    public static function primitives(): array
    {
        return [
            Fixture::analyser('B1', 'Plain/ReadsClock.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsClock
                {
                    public function stamp(): int
                    {
                        return time();
                    }
                }
                PHP, 'B1 —'),

            Fixture::analyser('B2', 'Plain/RollsDice.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class RollsDice
                {
                    public function pick(): int
                    {
                        return random_int(1, 6);
                    }
                }
                PHP, 'B2 —'),

            Fixture::analyser('B3', 'Plain/ReadsDisk.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class ReadsDisk
                {
                    public function contents(): string|false
                    {
                        return file_get_contents('/etc/hostname');
                    }
                }
                PHP, 'B3 —'),

            Fixture::analyser('B4', 'Plain/Waits.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class Waits
                {
                    public function hold(): void
                    {
                        sleep(1);
                    }
                }
                PHP, 'B4 —'),
        ];
    }
}
