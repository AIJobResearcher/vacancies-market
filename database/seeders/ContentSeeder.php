<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\ContentModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ContentSeeder extends Seeder
{
    private const int CHUNK = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            SourceModel::query()
                ->select('id')
                ->chunkById(
                    self::CHUNK,
                    fn ($sources) => $this->createContents($sources->pluck('id')->all()),
                );
        });
    }

    /** @param list<string> $sourceIds */
    private function createContents(array $sourceIds): void
    {
        if ($sourceIds === []) {
            return;
        }

        $contents = ContentModel::factory()
            ->count(count($sourceIds))
            ->state(new Sequence(
                static fn (Sequence $sequence): array => ['source_id' => $sourceIds[$sequence->index]],
            ))
            ->make();

        $rows = [];
        foreach ($contents as $content) {
            $rows[] = $content->getAttributes();
        }

        ContentModel::query()->insert($rows);
    }
}
