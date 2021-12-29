<?php

namespace App\Exceptions;

use Exception;

class CategoryNotFoundErrorException extends Exception
{
    public function report()
    {
        \Log::debug('Category not found');
    }
}
