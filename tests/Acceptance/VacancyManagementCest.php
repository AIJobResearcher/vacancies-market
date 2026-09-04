<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use AcceptanceTester;

class VacancyManagementCest
{
    /**
     * @Then response status is :code
     */
    public function responseStatusIs(AcceptanceTester $I, $code)
    {
        $I->seeResponseCodeIs($code);
    }
}