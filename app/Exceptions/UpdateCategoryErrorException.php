<?php

namespace App\Exceptions;

use Exception;

class UpdateCategoryErrorException extends Exception
{
    public function report()
    {
        \Log::debug("Error on updating a category");
    }
}
