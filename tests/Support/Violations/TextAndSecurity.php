<?php

declare(strict_types=1);

namespace Tests\Support\Violations;

use Tests\Support\Fixture;

/** Text as characters, and what may leave or enter the process. */
final readonly class TextAndSecurity
{
    /** @return list<Fixture> */
    public static function textAndSecurity(): array
    {
        return [
            Fixture::analyser('L3', 'Plain/CountsBytes.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class CountsBytes
                {
                    public function width(string $name): int
                    {
                        return strlen($name);
                    }
                }
                PHP, 'L3 —'),

            Fixture::analyser('L4', 'Plain/FormatsADate.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class FormatsADate
                {
                    public function shown(int $stamp): string
                    {
                        return gmdate('d/m/Y', $stamp);
                    }
                }
                PHP, 'L4 —'),

            Fixture::analyser('L5', 'Plain/FormatsANumber.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class FormatsANumber
                {
                    public function shown(float $size): string
                    {
                        return number_format($size, 2, '.', ',');
                    }
                }
                PHP, 'L5 —'),

            Fixture::analyser('L6', 'Plain/SortsByBytes.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class SortsByBytes
                {
                    public function order(string $a, string $b): int
                    {
                        return strcmp($a, $b);
                    }
                }
                PHP, 'L6 —'),

            Fixture::analyser('S1', 'Plain/RunsAProgram.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class RunsAProgram
                {
                    public function out(): mixed
                    {
                        return shell_exec('echo hello');
                    }
                }
                PHP, 'S1 —'),

            Fixture::suite('N1-R39', 'app-modules/operator/src/Internal/Screens/ShowsSomebodysStack.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens;

                use Illuminate\View\View;
                use Modules\Kernel\Api\Reading;
                use Native\Mobile\Edge\NativeComponent;

                final class ShowsSomebodysStack extends NativeComponent
                {
                    public function __construct(private Reading $reading) {}

                    public function render(): View
                    {
                        return view('operator::your-stacks');
                    }
                }
                PHP, 'N1-R39 —', 'ShowsSomebodysStack'),

            Fixture::suite('N4-R18', 'app-modules/operator/src/Internal/Screens/ShowsASessionOpenly.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Modules\Operator\Internal\Screens;

                use Illuminate\View\View;
                use Modules\Kernel\Api\Session;
                use Native\Mobile\Edge\NativeComponent;

                final class ShowsASessionOpenly extends NativeComponent
                {
                    public function __construct(private Session $session) {}

                    public function render(): View
                    {
                        return view('operator::your-stacks');
                    }
                }
                PHP, 'N4-R18 —', 'ShowsASessionOpenly'),

            Fixture::analyser('N4-R11', 'Plain/RaisesItsOwnAlert.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Native\Mobile\Facades\Dialog;

                final class RaisesItsOwnAlert
                {
                    public function tell(): void
                    {
                        Dialog::alert('Heads up', 'Something happened on your stack.');
                    }
                }
                PHP, 'N4-R11 —'),

            Fixture::analyser('N1-R21', 'Plain/TurnsVerificationOff.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use Illuminate\Http\Client\Factory;

                final class TurnsVerificationOff
                {
                    public function __construct(private Factory $http) {}

                    public function call(): void
                    {
                        $this->http->withoutVerifying()->get('https://example.test');
                    }
                }
                PHP, 'N1-R21 —'),

            Fixture::analyser('N1-R16', 'Plain/OpensItsOwnConnection.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                use CurlHandle;

                final class OpensItsOwnConnection
                {
                    public function byCurl(CurlHandle $handle): void
                    {
                        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, named: false);
                    }
                }
                PHP, 'N1-R16 —'),

            Fixture::suite('N1-R21', '.env.fixtureplanted', <<<'ENV'
                LEMONFIBER_VERIFY_TLS=true
                ENV, 'N1-R21 —', 'lemonfiber_verify_tls'),

            Fixture::analyser('S3', 'Plain/WeakensTls.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WeakensTls
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        return ['verify' => false, 'timeout' => 5];
                    }
                }
                PHP, 'S3 —'),

            // The literal is the easy half. A verification flag read from
            // configuration arrives as a string or through a variable, and the
            // rule used to read the node rather than ask the analyser — so
            // `'0'`, `''`, `null` and anything one line away all passed.
            Fixture::analyser('S3', 'Plain/WeakensTlsFromAVariable.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WeakensTlsFromAVariable
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        $verify = '0';

                        return ['verify' => $verify, 'timeout' => 5];
                    }
                }
                PHP, 'S3 —'),

            // The polarity half. `verify_expiry` asks for the expiry to be
            // checked, so `false` is the spelling that waives it — and while
            // this name sat in the off-when-true list the analyser refused
            // `true` and passed exactly this.
            Fixture::analyser('S3', 'Plain/WaivesTheExpiryCheck.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace Fixtures\Plain;

                final class WaivesTheExpiryCheck
                {
                    /** @return array<string, mixed> */
                    public function options(): array
                    {
                        return ['verify_expiry' => false, 'timeout' => 5];
                    }
                }
                PHP, 'S3 —'),
        ];
    }
}
