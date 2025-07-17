<?php

namespace App\Domain\Transaction\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class DuplicateTransactionException extends DomainException
{
    protected $message = 'Transacción duplicada detectada';
    protected $code = 400;
}