<?php

declare(strict_types=1);

namespace App\Application\UseCases;

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
     * @return LengthAwarePaginator<int, array{
     *     id: string,
     *     title: string,
     *     category: string,
     *     sub_category: string|null,
     *     parent_job_id: string|null,
     *     parent_job_title: string|null,
     * }>
     */
    public function handle(array $jobIds): LengthAwarePaginator
    {
        $result = $this->jobRepository->findPreviewsByIds($jobIds);

        return new LengthAwarePaginator($result['items'], $result['total'], max($result['total'], 1), 1);
    }
}
