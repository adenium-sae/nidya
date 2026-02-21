<?php

namespace App\Actions\Organization\Branches;

use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;

class UpdateBranchAction
{
    public function __invoke(string $id, array $data): Branch
    {
        /** @var Branch|null $branch */
        $branch = Branch::find($id);
        if (!$branch) {
            throw new BranchNotFoundException();
        }
        $branch->fill($data);
        $branch->save();
        return $branch->fresh(['store', 'address']);
    }
}
