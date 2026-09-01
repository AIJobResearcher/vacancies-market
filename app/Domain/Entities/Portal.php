<?php
declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use DateTimeImmutable;

final class Portal
{
    /**
     * External portal configuration (lookup)
     *
     * Contains parsing configuration used by importers. If a portal becomes
     * unreachable importers should emit `ExternalPortalUnreachable` domain event
     * (see `app/Domain/Events/ExternalPortalUnreachable.php`).
     *
     * parsingConfig is stored as array (JSON) and should be validated by the
     * import service according to portal-specific rules.
     */
    public readonly string $id;
    public string $name;
    public string $baseUrl;
    public ?string $apiEndpoint;
    /** @var array<string,mixed> */
    public array $parsingConfig = [];
    public int $crawlDelaySeconds = 0;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $name,
        string $baseUrl,
        ?string $apiEndpoint = null,
        array $parsingConfig = [],
        int $crawlDelaySeconds = 0,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        if ($name === '') {
            throw new PortalNameEmptyException();
        }
        if ($baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }

        $this->id = $id;
        $this->name = $name;
        $this->baseUrl = $baseUrl;
        $this->apiEndpoint = $apiEndpoint;
        $this->parsingConfig = $parsingConfig;
        $this->crawlDelaySeconds = $crawlDelaySeconds;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
    }
}