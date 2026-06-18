<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Employer;
use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\ValueObjects\Contacts;
use App\Infrastructure\Models\Employer as EmployerModel;

final class EmployerEloquentRepository implements EmployerRepositoryInterface
{
    public function save(Employer $employer): void
    {
        EmployerModel::updateOrCreate(
            ['id' => $employer->id],
            [
                'name' => $employer->name,
                'description' => $employer->description,
                'website' => $employer->website,
                'email' => $employer->contacts->email,
                'phone' => $employer->contacts->phone,
                'portal_id' => $employer->portalId,
            ]
        );
    }

    public function findById(string $id): ?Employer
    {
        $model = EmployerModel::find($id);
        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function remove(string $id): void
    {
        EmployerModel::destroy($id);
    }

    private function toDomain(EmployerModel $model): Employer
    {
        $contacts = new Contacts($model->email, $model->phone);
        return new Employer(
            $model->id,
            $model->name,
            $model->description,
            $model->website,
            $contacts,
            $model->portal_id,
            $model->created_at,
            $model->updated_at,
        );
    }
}

