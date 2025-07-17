<?php

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class DuplicateEmailException extends DomainException
{
    protected $message = 'Ya existe un usuario con este email';
    protected $code = 409;
}