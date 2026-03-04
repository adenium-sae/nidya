<?php

namespace App\Actions\Organization\Branches;

use App\Exceptions\Organization\Branches\BranchNotFoundException;
use App\Models\Branch;

class DeleteBranchAction
{
    public function __invoke(string $id): void
    {
        /** @var Branch|null $branch */
        $branch = Branch::find($id);

        if (!$branch) {
            throw new BranchNotFoundException();
        }

        $branch->delete();
    }
}
