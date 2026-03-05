<?php

use App\Actions\Organization\Stores\DeleteStoreAction;
use App\Exceptions\Organization\Stores\StoreNotFoundException;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it soft deletes a store', function () {
    $store = Store::factory()->create();

    $action = new DeleteStoreAction();
    $action($store->id);

    expect(Store::withTrashed()->find($store->id)->trashed())->toBeTrue();
});

test('it throws exception if store not found', function () {
    $action = new DeleteStoreAction();

    expect(fn() => $action('invalid-id'))->toThrow(StoreNotFoundException::class);
});
