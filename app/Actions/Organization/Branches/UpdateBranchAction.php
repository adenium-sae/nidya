<?php

namespace App\Actions\Organization\Branches;

use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class UpdateBranchAction
{
    public function __invoke(string $id, array $data): Branch
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var Branch|null $branch */
            $branch = Branch::find($id);
            if (!$branch) {
                throw new BranchNotFoundException();
            }
            $branch->fill($data);
            $branch->save();

            if (isset($data['store_ids'])) {
                $branch->stores()->sync($data['store_ids']);
            }

            return $branch->fresh(['stores', 'address']);
        });
    }
}
