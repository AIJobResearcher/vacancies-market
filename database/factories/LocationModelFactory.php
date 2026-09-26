<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enums\LocationTypeEnum;
use App\Infrastructure\Eloquents\Models\LocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<LocationModel>
 */
final class LocationModelFactory extends Factory
{
    public const int ALBANIA_ID = 8;

    public const int ANDORRA_ID = 20;

    public const int ARMENIA_ID = 51;

    public const int AZERBAIJAN_ID = 31;

    public const int BELARUS_ID = 112;

    public const int BELGIUM_ID = 56;

    public const int BOSNIA_AND_HERZEGOVINA_ID = 70;

    public const int CZECHIA_ID = 203;

    public const int DNIPRO_ID = 709930;

    public const int DONETSK_ID = 709717;

    public const int EUROPEAN_UNION_ID = 918;

    public const int EUROPE_ID = 150;

    public const int FRANCE_ID = 250;

    public const int GEORGIA_ID = 268;

    public const int GERMANY_ID = 276;

    public const int ICELAND_ID = 352;

    public const int ITALY_ID = 380;

    public const int KAZAKHSTAN_ID = 398;

    public const int KHARKIV_ID = 706483;

    public const int KRYVYI_RIH_ID = 703845;

    public const int KYIV_ID = 703448;

    public const int LIECHTENSTEIN_ID = 438;

    public const int LVIV_ID = 702550;

    public const int MOLDOVA_ID = 498;

    public const int MONTENEGRO_ID = 499;

    public const int MYKOLAIV_ID = 700569;

    public const int NETHERLANDS_ID = 528;

    public const int NORTH_MACEDONIA_ID = 807;

    public const int NORWAY_ID = 578;

    public const int ODESA_ID = 698740;

    public const int POLAND_ID = 616;

    public const int ROMANIA_ID = 642;

    public const int RUSSIA_ID = 643;

    public const int SERBIA_ID = 688;

    public const int SEVASTOPOL_ID = 694423;

    public const int SPAIN_ID = 724;

    public const int SWEDEN_ID = 752;

    public const int SWITZERLAND_ID = 756;

    public const int TURKEY_ID = 792;

    public const int UKRAINE_ID = 804;

    public const int UNITED_KINGDOM_ID = 826;

    public const int ZAPORIZHZHIA_ID = 687700;

    /** @var class-string<LocationModel> */
    protected $model = LocationModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => self::EUROPE_ID,
            'name' => 'Europe',
            'iso_name' => null,
            'parent_id' => null,
            'type' => LocationTypeEnum::REGION->value,
        ];
    }

    public function albania(): static
    {
        return $this->europeanCountry(self::ALBANIA_ID, 'Albania', 'AL');
    }

    public function andorra(): static
    {
        return $this->europeanCountry(self::ANDORRA_ID, 'Andorra', 'AD');
    }

    public function armenia(): static
    {
        return $this->europeanCountry(self::ARMENIA_ID, 'Armenia', 'AM');
    }

    public function azerbaijan(): static
    {
        return $this->europeanCountry(self::AZERBAIJAN_ID, 'Azerbaijan', 'AZ');
    }

    public function belarus(): static
    {
        return $this->europeanCountry(self::BELARUS_ID, 'Belarus', 'BY');
    }

    public function belgium(): static
    {
        return $this->europeanCountry(self::BELGIUM_ID, 'Belgium', 'BE');
    }

    public function bosniaAndHerzegovina(): static
    {
        return $this->europeanCountry(self::BOSNIA_AND_HERZEGOVINA_ID, 'Bosnia and Herzegovina', 'BA');
    }

    public function czechia(): static
    {
        return $this->europeanCountry(self::CZECHIA_ID, 'Czechia', 'CZ');
    }

    public function dnipro(): static
    {
        return $this->ukrainianCity(self::DNIPRO_ID, 'Dnipro');
    }

    public function donetsk(): static
    {
        return $this->ukrainianCity(self::DONETSK_ID, 'Donetsk');
    }

    public function europe(): static
    {
        return $this->state($this->definition());
    }

    public function europeanUnion(): static
    {
        return $this->state([
            'id' => self::EUROPEAN_UNION_ID,
            'name' => 'European Union',
            'iso_name' => 'EU',
            'parent_id' => self::EUROPE_ID,
            'type' => LocationTypeEnum::UNIFICATION_OF_COUNTRIES->value,
        ]);
    }

    public function france(): static
    {
        return $this->europeanCountry(self::FRANCE_ID, 'France', 'FR');
    }

    public function georgia(): static
    {
        return $this->europeanCountry(self::GEORGIA_ID, 'Georgia', 'GE');
    }

    public function germany(): static
    {
        return $this->europeanCountry(self::GERMANY_ID, 'Germany', 'DE');
    }

    public function iceland(): static
    {
        return $this->europeanCountry(self::ICELAND_ID, 'Iceland', 'IS');
    }

    public function italy(): static
    {
        return $this->europeanCountry(self::ITALY_ID, 'Italy', 'IT');
    }

    public function kazakhstan(): static
    {
        return $this->europeanCountry(self::KAZAKHSTAN_ID, 'Kazakhstan', 'KZ');
    }

    public function kharkiv(): static
    {
        return $this->ukrainianCity(self::KHARKIV_ID, 'Kharkiv');
    }

    public function kryvyiRih(): static
    {
        return $this->ukrainianCity(self::KRYVYI_RIH_ID, 'Kryvyi Rih');
    }

    public function kyiv(): static
    {
        return $this->ukrainianCity(self::KYIV_ID, 'Kyiv', 'UA-30');
    }

    public function liechtenstein(): static
    {
        return $this->europeanCountry(self::LIECHTENSTEIN_ID, 'Liechtenstein', 'LI');
    }

    public function lviv(): static
    {
        return $this->ukrainianCity(self::LVIV_ID, 'Lviv');
    }

    public function moldova(): static
    {
        return $this->europeanCountry(self::MOLDOVA_ID, 'Moldova', 'MD');
    }

    public function montenegro(): static
    {
        return $this->europeanCountry(self::MONTENEGRO_ID, 'Montenegro', 'ME');
    }

    public function mykolaiv(): static
    {
        return $this->ukrainianCity(self::MYKOLAIV_ID, 'Mykolaiv');
    }

    public function netherlands(): static
    {
        return $this->europeanCountry(self::NETHERLANDS_ID, 'Netherlands', 'NL');
    }

    public function northMacedonia(): static
    {
        return $this->europeanCountry(self::NORTH_MACEDONIA_ID, 'North Macedonia', 'MK');
    }

    public function norway(): static
    {
        return $this->europeanCountry(self::NORWAY_ID, 'Norway', 'NO');
    }

    public function odesa(): static
    {
        return $this->ukrainianCity(self::ODESA_ID, 'Odesa');
    }

    public function poland(): static
    {
        return $this->europeanCountry(self::POLAND_ID, 'Poland', 'PL');
    }

    public function romania(): static
    {
        return $this->europeanCountry(self::ROMANIA_ID, 'Romania', 'RO');
    }

    public function russia(): static
    {
        return $this->europeanCountry(self::RUSSIA_ID, 'Russia', 'RU');
    }

    public function serbia(): static
    {
        return $this->europeanCountry(self::SERBIA_ID, 'Serbia', 'RS');
    }

    public function sevastopol(): static
    {
        return $this->ukrainianCity(self::SEVASTOPOL_ID, 'Sevastopol', 'UA-40');
    }

    public function spain(): static
    {
        return $this->europeanCountry(self::SPAIN_ID, 'Spain', 'ES');
    }

    public function sweden(): static
    {
        return $this->europeanCountry(self::SWEDEN_ID, 'Sweden', 'SE');
    }

    public function switzerland(): static
    {
        return $this->europeanCountry(self::SWITZERLAND_ID, 'Switzerland', 'CH');
    }

    public function turkey(): static
    {
        return $this->europeanCountry(self::TURKEY_ID, 'Turkey', 'TR');
    }

    public function ukraine(): static
    {
        return $this->europeanCountry(self::UKRAINE_ID, 'Ukraine', 'UA');
    }

    public function unitedKingdom(): static
    {
        return $this->europeanCountry(self::UNITED_KINGDOM_ID, 'United Kingdom', 'GB');
    }

    public function zaporizhzhia(): static
    {
        return $this->ukrainianCity(self::ZAPORIZHZHIA_ID, 'Zaporizhzhia');
    }

    private function europeanCountry(int $id, string $name, string $isoName): static
    {
        return $this->state([
            'id' => $id,
            'name' => $name,
            'iso_name' => $isoName,
            'parent_id' => self::EUROPE_ID,
            'type' => LocationTypeEnum::COUNTRY->value,
        ]);
    }

    private function ukrainianCity(int $id, string $name, ?string $isoName = null): static
    {
        return $this->state([
            'id' => $id,
            'name' => $name,
            'iso_name' => $isoName,
            'parent_id' => self::UKRAINE_ID,
            'type' => LocationTypeEnum::CITY->value,
        ]);
    }
}
