<?php

namespace App\Exceptions;

use Exception;

class CreateCategoryErrorException extends Exception
{
    public function report()
    {
        \Log::debug("Error on creating a category");
    }
}
