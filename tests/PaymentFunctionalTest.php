<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;

class PaymentFunctionalTest extends WebTestCase
{
    public function testSuccessful()
    {
        $parameters = [
            'invoice_id'       => mt_rand(1, mt_getrandmax()),
            'transaction_hash' => uniqid('', true),
            'value'            => mt_rand(1, mt_getrandmax()),
            'secret'           => $_ENV['BLOCKCHAIN_SECRET'],
        ];

        $url = '/invoice?'.http_build_query($parameters);

        $client = self::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*ok*', $response->getContent());

        $invoiceRepository = static::$container->get(InvoiceRepository::class);
        $invoice = $invoiceRepository->findOneBy([
            'invoice_id'       => $parameters['invoice_id'],
            'transaction_hash' => $parameters['transaction_hash'],
            'value'            => $parameters['value'],
        ]);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertInstanceOf(\DateTime::class, $invoice->getDateTime());
    }

    public function testAccessDeniedWhenSecretIsInvalid()
    {
        $parameters = [
            'invoice_id'       => mt_rand(1, mt_getrandmax()),
            'transaction_hash' => uniqid('', true),
            'value'            => mt_rand(1, mt_getrandmax()),
            'secret'           => uniqid() // invalid,
        ];

        $url = '/invoice?'.http_build_query($parameters);

        $client = self::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();

        $this->assertEquals(403, $response->getStatusCode());
    }
}
