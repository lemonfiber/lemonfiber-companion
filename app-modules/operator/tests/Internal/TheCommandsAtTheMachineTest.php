<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function expect;
use function it;

use Modules\Operator\Internal\TheCommandsAtTheMachine;

it('names the commands that start the stack with the machine, or stop it doing so', function (): void {
    expect(TheCommandsAtTheMachine::installingAutostart())->toBe('lemonfiber hosting install boot')
        ->and(TheCommandsAtTheMachine::removingAutostart())->toBe('lemonfiber hosting remove boot')
        ->and(TheCommandsAtTheMachine::AUTOSTART)->toBe('boot');
});

it('names a plain credential bare, and quotes any other name so the stack can put nothing else on the line', function (string $key, string $command): void {
    expect(TheCommandsAtTheMachine::settingACredential($key))->toBe($command);
})->with([
    'a plain name' => ['USENET_PASS', 'lemonfiber config set USENET_PASS <value>'],
    'a plain name in lower case' => ['_indexer_key2', 'lemonfiber config set _indexer_key2 <value>'],
    'a name starting with a digit' => ['2FA', "lemonfiber config set '2FA' <value>"],
    'a name with a path in it' => ['decline/jellyfin.key', "lemonfiber config set 'decline/jellyfin.key' <value>"],
    'a name carrying a command' => ['X; rm -rf ~', "lemonfiber config set 'X; rm -rf ~' <value>"],
    'a name carrying a quote' => ["it's", "lemonfiber config set 'it'\\''s' <value>"],
    'a name ending in a line' => ["KEY\n", "lemonfiber config set 'KEY\n' <value>"],
]);
