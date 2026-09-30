<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Factories\LocationModelFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            LocationModelFactory::new()->europe()->create();

            $this->seedCountries();
            $this->seedUkrainianCities();
        });
    }

    private function seedCountries(): void
    {
        LocationModelFactory::new()->europeanUnion()->create();
        LocationModelFactory::new()->albania()->create();
        LocationModelFactory::new()->andorra()->create();
        LocationModelFactory::new()->armenia()->create();
        LocationModelFactory::new()->azerbaijan()->create();
        LocationModelFactory::new()->belarus()->create();
        LocationModelFactory::new()->belgium()->create();
        LocationModelFactory::new()->bosniaAndHerzegovina()->create();
        LocationModelFactory::new()->czechia()->create();
        LocationModelFactory::new()->france()->create();
        LocationModelFactory::new()->georgia()->create();
        LocationModelFactory::new()->germany()->create();
        LocationModelFactory::new()->iceland()->create();
        LocationModelFactory::new()->italy()->create();
        LocationModelFactory::new()->kazakhstan()->create();
        LocationModelFactory::new()->liechtenstein()->create();
        LocationModelFactory::new()->moldova()->create();
        LocationModelFactory::new()->montenegro()->create();
        LocationModelFactory::new()->netherlands()->create();
        LocationModelFactory::new()->northMacedonia()->create();
        LocationModelFactory::new()->norway()->create();
        LocationModelFactory::new()->poland()->create();
        LocationModelFactory::new()->romania()->create();
        LocationModelFactory::new()->russia()->create();
        LocationModelFactory::new()->serbia()->create();
        LocationModelFactory::new()->spain()->create();
        LocationModelFactory::new()->sweden()->create();
        LocationModelFactory::new()->switzerland()->create();
        LocationModelFactory::new()->turkey()->create();
        LocationModelFactory::new()->ukraine()->create();
        LocationModelFactory::new()->unitedKingdom()->create();
    }

    private function seedUkrainianCities(): void
    {
        LocationModelFactory::new()->dnipro()->create();
        LocationModelFactory::new()->donetsk()->create();
        LocationModelFactory::new()->kharkiv()->create();
        LocationModelFactory::new()->kryvyiRih()->create();
        LocationModelFactory::new()->kyiv()->create();
        LocationModelFactory::new()->lviv()->create();
        LocationModelFactory::new()->mykolaiv()->create();
        LocationModelFactory::new()->odesa()->create();
        LocationModelFactory::new()->sevastopol()->create();
        LocationModelFactory::new()->zaporizhzhia()->create();
    }
}
