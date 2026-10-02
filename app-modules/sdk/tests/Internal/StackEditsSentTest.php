<?php

declare(strict_types=1);

use Modules\Kernel\Api\AStackEditCannotBeShown;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Sdk\Api\StackEditsAreUnreadable;
use Modules\Sdk\Api\WireField;
use Modules\Sdk\Internal\StackEditsSent;

it('reads every edited file under the field it was asked about, in order', function (): void {
    $edits = StackEditsSent::in(['stack_edits' => [
        ['path' => 'compose.yaml', 'diff' => "- a\n+ b\n"],
        ['path' => 'env/sonarr.env', 'diff' => ''],
    ]], WireField::StackEdits);
    $paths = [];

    foreach ($edits as $edit) {
        $paths[] = $edit->path();
    }

    expect($paths)->toBe(['compose.yaml', 'env/sonarr.env']);
});

it('reads an empty list as no file edited', function (): void {
    expect(StackEditsSent::in(['stack_edits' => []], WireField::StackEdits))->toEqual(TheStackEdits::none());
});

it('refuses a list that is missing or is not one, naming the field', function (array $data): void {
    expect(static fn(): TheStackEdits => StackEditsSent::in($data, WireField::StackEdits))
        ->toThrow(StackEditsAreUnreadable::class, '`stack_edits`');
})->with([
    'missing' => [[]],
    'not a list' => [['stack_edits' => ['first' => []]]],
]);

it('refuses a file it cannot read, naming where it sat', function (mixed $edit): void {
    expect(static fn(): TheStackEdits => StackEditsSent::in(['stack_edits' => [['path' => 'a', 'diff' => ''], $edit]], WireField::StackEdits))
        ->toThrow(StackEditsAreUnreadable::class, 'file 2');
})->with([
    'not a file' => ['compose.yaml'],
    'no path' => [['diff' => '']],
    'a diff that is not text' => [['path' => 'compose.yaml', 'diff' => 3]],
]);

it('refuses a diff line marked as neither side, as the kernel does', function (): void {
    expect(static fn(): TheStackEdits => StackEditsSent::in(['stack_edits' => [['path' => 'compose.yaml', 'diff' => "unmarked\n"]]], WireField::StackEdits))
        ->toThrow(AStackEditCannotBeShown::class);
});
