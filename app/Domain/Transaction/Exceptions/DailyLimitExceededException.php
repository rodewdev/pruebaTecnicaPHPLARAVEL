<?php

namespace App\Domain\Transaction\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class DailyLimitExceededException extends DomainException
{
    protected $message = 'Límite diario de transferencias excedido';
    protected $code = 400;
}