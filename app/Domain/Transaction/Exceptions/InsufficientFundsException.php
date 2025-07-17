<?php

namespace App\Domain\Transaction\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class InsufficientFundsException extends DomainException
{
    protected $message = 'Fondos insuficientes para realizar la transferencia';
    protected $code = 400;
}