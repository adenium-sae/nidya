<?php

namespace App\Exceptions\Catalog\Categories;

use App\Exceptions\ClientException;

class CategoryNotFoundException extends ClientException
{
    public function __construct()
    {
        parent::__construct(
            "CATEGORY_NOT_FOUND",
            __('exceptions.category_not_found'),
            404
        );
    }
}
