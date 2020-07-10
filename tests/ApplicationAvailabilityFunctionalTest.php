<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     * @testdox      page successful: '$url'
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
        $locales = ['en', 'es'];
        $paths = [
            '/about',
            '/doc/class-builder/master/index.html',
            '/doc/class-builder/1.0/index.html',
            '/doc/cli/master/index.html',
            '/doc/cli/1.0/index.html',
            '/doc/components/master/index.html',
            '/doc/components/1.0/index.html',
            '/doc/composed-views/master/index.html',
            '/doc/composed-views/1.0/index.html',
            '/doc/pyramidal-tests/master/index.html',
            '/doc/pyramidal-tests/1.1/index.html',
        ];

        foreach ($locales as $locale) {
            foreach ($paths as $path) {
                yield ["/{$locale}{$path}"];
            }
        }
    }
}
