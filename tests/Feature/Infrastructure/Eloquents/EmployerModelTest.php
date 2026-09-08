<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmployerModelTest extends TestCase
{
    use RefreshDatabase;

    public function testEmployerPersistsAndRestores(): void
    {
        $model = new EmployerModel();
        $model->id = '11111111-1111-1111-1111-111111111111';
        $model->title = 'Acme';
        $model->description = 'Description';
        $model->website = 'https://acme.test';
        $model->email = 'hr@acme.test';
        $model->phone = '+123';
        $model->logo_url = 'https://acme.test/logo.png';
        $model->version = 1;
        $model->save();

        $loaded = EmployerModel::query()->findOrFail('11111111-1111-1111-1111-111111111111');

        $this->assertSame('Acme', $loaded->title);
        $this->assertSame('https://acme.test', $loaded->website);
        $this->assertSame(1, $loaded->version);
        $this->assertNotNull($loaded->created_at);
        $this->assertNotNull($loaded->updated_at);
    }
}
