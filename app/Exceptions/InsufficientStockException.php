<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $productName, float $available, float $requested)
    {
        parent::__construct(
            "Stock insuficiente para '{$productName}'. Disponible: {$available}, Solicitado: {$requested}."
        );
    }
}
