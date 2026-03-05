<?php

use App\Actions\Organization\Branches\DeleteBranchAction;
use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it soft deletes a branch', function () {
    $branch = Branch::factory()->create();

    $action = new DeleteBranchAction();
    $action($branch->id);

    expect(Branch::withTrashed()->find($branch->id)->trashed())->toBeTrue();
});

test('it throws exception if branch not found', function () {
    $action = new DeleteBranchAction();

    expect(fn() => $action('invalid-id'))->toThrow(BranchNotFoundException::class);
});
