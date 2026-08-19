<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Exceptions;

/**
 * Thrown when network connection, HTTP transfer, or SSL handshake fails.
 */
class NetworkException extends ScraperException
{
    private ?int $statusCode;

    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, ?int $statusCode = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
