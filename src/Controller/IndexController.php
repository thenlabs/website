<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
