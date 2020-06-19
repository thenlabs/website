<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function urlProvider()
    {
        yield ['/'];
        yield ['/page/about'];
        // yield ['/page/faq'];
        yield ['/docs/pyramidal-tests/master/es/index.html'];
        yield ['/docs/components/master/es/index.html'];
        yield ['/docs/class-builder/master/es/index.html'];
        yield ['/docs/cli/master/es/index.html'];
        yield ['/docs/composed-views/master/es/index.html'];
    }
}
