<?php

namespace App\Exceptions;

use Exception;

class ImmutableDocumentException extends Exception
{
    public function __construct(string $docType, string $docNumber, string $status)
    {
        parent::__construct(
            "El documento {$docType} #{$docNumber} está en estado '{$status}' y no puede ser modificado."
        );
    }
}
