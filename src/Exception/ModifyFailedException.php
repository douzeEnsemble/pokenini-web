<?php

declare(strict_types=1);

namespace App\Exception;

class ModifyFailedException extends \RuntimeException
{
    protected $message = 'Fail to modify resources';
}
