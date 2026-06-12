<?php

namespace App\Exceptions;

use Exception;

class PaymentExceedsBalanceException extends Exception
{
    public function __construct(float $balance, float $amount)
    {
        parent::__construct(
            "El monto del pago ({$amount}) supera el saldo pendiente ({$balance})."
        );
    }
}
