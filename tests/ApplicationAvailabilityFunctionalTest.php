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

    public function testRedirectToEnglishByDefault()
    {
        $client = self::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/en/');
    }

    public function testRedirectToSpanishWhenThatIsThePreferredLanguageOfTheBrowser()
    {
        $client = self::createClient([], [
            'HTTP_ACCEPT-LANGUAGE' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
        ]);

        $client->request('GET', '/');

        $this->assertResponseRedirects('/es/');
    }

    public function urlProvider()
    {
        $locales = ['en', 'es'];

        foreach ($locales as $locale) {
            yield ["/{$locale}/"];
        }

        $doc = [
            'class-builder'   => ['master', '1.0'],
            'cli'             => ['master', '1.0'],
            'components'      => ['master', '1.0'],
            'composed-views'  => ['master', '1.0'],
            'pyramidal-tests' => ['master', '1.1'],
        ];

        foreach ($locales as $locale) {
            foreach ($doc as $project => $versions) {
                foreach ($versions as $version) {
                    yield ["/{$locale}/doc/{$project}/{$version}/index.html"];
                }
            }
        }
    }
}
