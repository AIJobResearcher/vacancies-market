<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\DTOs\JobPreviewDto;
use App\Domain\Repositories\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetJobsByIdsUseCase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly JobRepositoryInterface $jobRepository)
    {
    }

    /**
     * @param list<string> $jobIds
     * @return LengthAwarePaginator<int, JobPreviewDto>
     */
    public function handle(array $jobIds): LengthAwarePaginator
    {
        $page = $this->jobRepository->findPreviewsByIds($jobIds);

        return new LengthAwarePaginator($page->items, $page->total, max($page->total, 1), 1);
    }
}
