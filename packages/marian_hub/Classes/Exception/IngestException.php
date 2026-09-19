<?php

declare(strict_types=1);

namespace Marian\Hub\Exception;

/**
 * Fehler beim Entgegennehmen von Messwerten. Der HTTP-Status steckt im Code.
 */
class IngestException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $status = 400)
    {
        parent::__construct($message, $status);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
