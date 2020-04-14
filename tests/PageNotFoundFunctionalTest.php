<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageNotFoundFunctionalTest extends WebTestCase
{
    public function testPageNotFound()
    {
        $client = self::createClient();
        $client->request('GET', '/docs/unexistent-project/master/es/index.html');

        $this->assertResponseStatusCodeSame(404);
    }
}
