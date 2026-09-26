<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\EmployerModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property string|null $website
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $logo_url
 * @property int $version
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 */
final class EmployerModel extends Model
{
    /** @use HasFactory<EmployerModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'employers';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'title',
        'description',
        'website',
        'email',
        'phone',
        'logo_url',
        'version',
    ];

    protected static function newFactory(): EmployerModelFactory
    {
        return EmployerModelFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
