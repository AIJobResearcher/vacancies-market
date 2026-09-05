<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\PortalModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PortalModelTest extends TestCase
{
    use RefreshDatabase;

    public function testPortalPersistsAndRestores(): void
    {
        $model = new PortalModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->name = 'LinkedIn';
        $model->base_url = 'https://linkedin.com';
        $model->api_endpoint = 'https://api.linkedin.com';
        $model->crawl_delay_seconds = 5;
        $model->version = 1;
        $model->save();

        $loaded = PortalModel::query()->findOrFail('99999999-9999-9999-9999-999999999999');

        $this->assertSame('LinkedIn', $loaded->name);
        $this->assertSame('https://linkedin.com', $loaded->base_url);
        $this->assertSame(5, $loaded->crawl_delay_seconds);
        $this->assertSame(1, $loaded->version);
    }
}
