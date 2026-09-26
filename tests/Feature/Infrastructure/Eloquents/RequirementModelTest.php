<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RequirementModelTest extends TestCase
{
    use RefreshDatabase;

    public function testRequirementPersistsAndRestores(): void
    {
        $model = new RequirementModel();
        $model->id = '88888888-8888-8888-8888-888888888888';
        $model->title = 'PHP';
        $model->description = 'PHP 8.5';
        $model->category = 'technical';
        $model->save();

        $loaded = RequirementModel::query()->findOrFail('88888888-8888-8888-8888-888888888888');

        $this->assertSame('PHP', $loaded->title);
        $this->assertSame('PHP 8.5', $loaded->description);
        $this->assertSame('technical', $loaded->category);
    }
}
