<?php

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class UserNotFoundException extends DomainException
{
    protected $message = 'Usuario no encontrado';
    protected $code = 404;
}