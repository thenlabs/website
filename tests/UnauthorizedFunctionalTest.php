<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnauthorizedFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function urlProvider()
    {
        yield ['/admin'];
    }
}
