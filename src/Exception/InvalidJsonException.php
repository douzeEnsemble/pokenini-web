<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidJsonException extends \RuntimeException
{
    protected $message = 'Json is invalid';
}
