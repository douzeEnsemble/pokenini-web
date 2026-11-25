<?php

declare(strict_types=1);

namespace App\Exception;

final class EmptyContentException extends \RuntimeException
{
    #[\Override]
    protected $message = 'Content cannot be empty';
}
