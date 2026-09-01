<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ExternalUrlInvalidException;
use App\Domain\Exceptions\ValidationException\ExternalUrlsEmptyException;

final class ExternalUrls
{
    /** @var string[] */
    private array $urls;

    public function __construct(array $urls)
    {
        $this->setUrls($urls);
    }

    private function setUrls(array $urls): void
    {
        $urls = array_values(array_unique($urls));
        if (empty($urls)) {
            throw new ExternalUrlsEmptyException();
        }
        foreach ($urls as $url) {
            if (!is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new ExternalUrlInvalidException($url);
            }
        }
        $this->urls = $urls;
    }

    /** @return string[] */
    public function toArray(): array
    {
        return $this->urls;
    }

    public function equals(ExternalUrls $other): bool
    {
        return $this->urls === $other->urls;
    }
}