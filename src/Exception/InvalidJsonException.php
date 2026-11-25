<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidJsonException extends \RuntimeException
{
    #[\Override]
    protected $message = 'Json is invalid';
}
