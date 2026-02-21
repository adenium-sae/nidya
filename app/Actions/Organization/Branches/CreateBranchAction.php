<?php

namespace App\Actions\Organization\Branches;

use App\Models\Branch;

class CreateBranchAction
{
    public function __invoke(array $data): Branch
    {
        return Branch::create($data);
    }
}
