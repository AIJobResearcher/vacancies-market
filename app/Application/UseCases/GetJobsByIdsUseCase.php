<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Repositories\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetJobsByIdsUseCase
{
    public function __construct(private readonly JobRepositoryInterface $jobRepository)
    {
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(array $jobIds): LengthAwarePaginator
    {
        $result = $this->jobRepository->findPreviewsByIds($jobIds);

        return new LengthAwarePaginator($result['items'], $result['total'], max($result['total'], 1), 1);
    }
}
