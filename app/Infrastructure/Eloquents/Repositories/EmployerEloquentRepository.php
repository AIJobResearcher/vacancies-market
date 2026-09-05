<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Employer;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Infrastructure\Eloquents\Mappers\EmployerMapper;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\OutboxMessageModel;
use Illuminate\Support\Facades\DB;
use Override;

final class EmployerEloquentRepository implements EmployerRepositoryInterface
{
    public function __construct(private readonly EmployerMapper $mapper)
    {
    }

    #[Override]
    public function findById(EmployerId $id): ?Employer
    {
        $model = EmployerModel::query()->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Employer $employer): void
    {
        DB::transaction(function () use ($employer): void {
            $events = $employer->releaseEvents();

            $this->persistRoot($employer);

            foreach ($events as $event) {
                OutboxMessageModel::query()->create([
                    'event_id' => $event->eventId,
                    'event_type' => $event->eventType,
                    'payload' => json_encode($event, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    private function persistRoot(Employer $employer): void
    {
        $state = $this->mapper->toPersistenceState($employer);

        if (EmployerModel::query()->whereKey($employer->id()->value())->exists()) {
            $expected = $employer->version() - 1;
            $affected = EmployerModel::query()
                ->whereKey($employer->id()->value())
                ->where('version', $expected)
                ->update($state);

            if ($affected === 0) {
                $existing = EmployerModel::query()
                    ->whereKey($employer->id()->value())
                    ->first(['version']);

                $actual = $existing === null ? 0 : $existing->version;

                throw new VersionConflictException(
                    'Employer',
                    $employer->id()->value(),
                    $expected,
                    $actual,
                );
            }
        } else {
            EmployerModel::query()->create($state);
        }
    }
}
