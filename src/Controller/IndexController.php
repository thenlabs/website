<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Entity\Invoice;

class IndexController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/invoice", name="invoice")
     */
    public function invoice(Request $request)
    {
        $secret = $request->query->get('secret');
        if ($secret != $_ENV['BLOCKCHAIN_SECRET']) {
            throw new AccessDeniedHttpException;
        }

        $invoiceId = $request->query->get('invoice_id');
        $transactionHash = $request->query->get('transaction_hash');
        $value = $request->query->get('value');

        $invoice = new Invoice;
        $invoice->setInvoiceId($invoiceId);
        $invoice->setTransactionHash($transactionHash);
        $invoice->setValue($value);
        $invoice->setDateTime(new \DateTime);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($invoice);
        $entityManager->flush();

        return new Response('*ok*');
    }

    /**
     * @Route("/invoiceAddress", name="invoice_address")
     */
    public function invoiceAddress()
    {
        $xpub = $_ENV['BLOCKCHAIN_XPUB'];
        $apiKey = $_ENV['BLOCKCHAIN_API_KEY'];

        $invoiceId = time();

        $routeParameters = [
            'invoice_id' => $invoiceId,
            'secret' => $_ENV['BLOCKCHAIN_SECRET'],
        ];

        $callbackUrl = $this->generateUrl(
            'invoice',
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $root_url = 'https://api.blockchain.info/v2/receive';
        $parameters = http_build_query([
            'xpub' => $xpub,
            'key' => $apiKey,
            'callback' => $callbackUrl,
        ]);

        $url = $root_url.'?'.$parameters;

        $response = file_get_contents($url);
        $object = json_decode($response);

        if (is_object($object) && isset($object->address)) {
            return new JsonResponse(['address' => $object->address]);
        } else {
            throw new Exception;
        }
    }
}
