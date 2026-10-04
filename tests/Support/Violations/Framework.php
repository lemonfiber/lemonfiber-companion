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

            // Beside the store and not in it: a directory whose name starts
            // with the store's, so a wall read as a string prefix rather than
            // as a directory would let the framework through.
            Fixture::suite('A7', 'app-modules/health/src/Internal/Storefront/NamesTheFrameworkBesideTheStore.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal\Storefront;

                use Illuminate\Support\Collection;

                final readonly class NamesTheFrameworkBesideTheStore
                {
                    public function holds(): Collection
                    {
                        return new Collection();
                    }
                }
                PHP, 'A7/E4 — health'),

            // A capability reaching the database from outside its store: the
            // rest of the module is as free of the framework as it ever was.
            Fixture::suite('A1', 'app-modules/health/src/Internal/KeepsBesideTheStore.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal;

                use Illuminate\Database\ConnectionInterface;

                final readonly class KeepsBesideTheStore
                {
                    public function __construct(private ConnectionInterface $database) {}

                    public function kept(): int
                    {
                        return $this->database->table('health_readings')->count();
                    }
                }
                PHP, 'A1 — Illuminate\\Database is named only in a capability\'s store'),

            // Planted in an adapter, which may name the framework, so that what
            // this breaks is which module may reach the database rather than
            // which kind of module may reach for Illuminate at all: a store is
            // a capability's, and an adapter is not one.
            Fixture::suite('A1', 'app-modules/codes/src/Fixtures/ReachesTheDatabase.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Codes\Fixtures;

                use Illuminate\Database\ConnectionInterface;

                final readonly class ReachesTheDatabase
                {
                    public function __construct(private ConnectionInterface $database) {}

                    public function kept(): int
                    {
                        return $this->database->table('codes_drawn')->count();
                    }
                }
                PHP, 'A1 — Illuminate\\Database is named only in a capability\'s store'),

            // A table created in the right place under the wrong name: a
            // capability's own migration, making a table that does not say
            // whose it is.
            Fixture::suite('A10', 'app-modules/health/database/migrations/2026_09_29_000001_create_readings_nobody_owns.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Illuminate\Database\Migrations\Migration;
                use Illuminate\Database\Schema\Blueprint;
                use Illuminate\Support\Facades\Schema;

                return new class extends Migration {
                    public function up(): void
                    {
                        Schema::create('readings', static function (Blueprint $table): void {
                            $table->string('stack_hash')->primary();
                        });
                    }
                };
                PHP, 'A10 — a table carries its owner'),

            // A module reading a table another store owns, which is the store
            // growing by the back door.
            Fixture::suite('A10', 'app-modules/codes/src/Fixtures/ReadsAnotherStoresTable.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Codes\Fixtures;

                final readonly class ReadsAnotherStoresTable
                {
                    public function table(): string
                    {
                        return 'health_readings';
                    }
                }
                PHP, 'A10 — no module names a table another module owns'),

            // The store reached from beside it rather than through its port,
            // by a relative name that imports nothing, which is how a class in
            // the same module would write it.
            Fixture::suite('A11', 'app-modules/health/src/Internal/AsksTheStoreDirectly.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal;

                use Modules\Kernel\Api\SealedStack;

                final readonly class AsksTheStoreDirectly
                {
                    public function __construct(private Store\HealthReadingsInTheDatabase $store) {}

                    public function forget(SealedStack $stack): int
                    {
                        return $this->store->forget($stack)->howMany();
                    }
                }
                PHP, 'A11 — nothing outside a store names a class in it'),

            // The rest of a capability reaching the store kit beside its store,
            // which is a second way into its table that no port stands before.
            Fixture::suite('A14', 'app-modules/health/src/Internal/KeepsThroughTheKit.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal;

                use Modules\Kernel\Api\Forgotten;
                use Modules\StoreKit\Api\ATableOfReadings;

                final readonly class KeepsThroughTheKit
                {
                    public function __construct(private ATableOfReadings $table) {}

                    public function forgetEverything(): Forgotten
                    {
                        return $this->table->forgetEverything();
                    }
                }
                PHP, 'A14 — a store kit is named only in a capability\'s store'),

            // The store kit reaching past the database for the rest of the
            // framework, which a store kit may not.
            Fixture::suite('A7', 'app-modules/store-kit/src/Fixtures/NamesMoreThanTheDatabase.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\StoreKit\Fixtures;

                use Illuminate\Support\Collection;

                final readonly class NamesMoreThanTheDatabase
                {
                    public function holds(): Collection
                    {
                        return new Collection();
                    }
                }
                PHP, 'A7/E4 — store-kit', 'Illuminate'),

            // A store that can be handed a summary it could read, and so one
            // day writes it to disk in the clear.
            Fixture::suite('A12', 'app-modules/health/src/Internal/Store/KeepsWhatItCanRead.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal\Store;

                use Modules\Kernel\Api\Noted;
                use Modules\Kernel\Api\TheHealthSummary;

                final readonly class KeepsWhatItCanRead
                {
                    public function keep(TheHealthSummary $summary): Noted
                    {
                        return Noted::notKept();
                    }
                }
                PHP, 'A12 — a store class takes and gives only sealed payloads'),

            // A class that is no owner's writer making a value to seal, and so a
            // second way into the phone's storage nothing lists.
            Fixture::suite('A13', 'app-modules/health/src/Internal/KeepsWhatItWasNeverAsked.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Health\Internal;

                use Modules\Kernel\Api\Unsealed;

                final readonly class KeepsWhatItWasNeverAsked
                {
                    public static function written(string $said): Unsealed
                    {
                        return Unsealed::of($said);
                    }
                }
                PHP, 'A13 — only a declared writer puts a value in the clear'),

            // A writer handed a session, one line from writing it down beside
            // the settings it keeps.
            Fixture::edit(
                'A13',
                'app-modules/connection/src/Internal/TheSettingsAsKept.php',
                'public static function written(ThePhonesSettings $settings): Unsealed',
                'public static function written(ThePhonesSettings $settings, \\Modules\\Kernel\\Api\\Session $session): Unsealed',
                'A13 — no writer is handed',
                'Session',
            ),

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

            // The same work, moved into a method `register()` calls: it still
            // runs at boot, before the first frame.
            Fixture::analyser('A9', 'Plain/ProviderWorkingThroughAMethod.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Support\ServiceProvider;

                final class ProviderWorkingThroughAMethod extends ServiceProvider
                {
                    public function register(): void
                    {
                        $this->bindTheStores();
                    }

                    private function bindTheStores(): void
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
