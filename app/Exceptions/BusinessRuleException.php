<?php

// app/Exceptions/BusinessRuleException.php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis yang pesannya aman ditampilkan ke pengguna.
 * $message berisi kunci terjemahan (lang/{id,en}/messages.php).
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $translationKey, public readonly array $replace = [])
    {
        parent::__construct($translationKey);
    }

    public function userMessage(): string
    {
        return __($this->getMessage(), $this->replace);
    }
}
