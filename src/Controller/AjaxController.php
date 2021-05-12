<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use App\Form\Type\NewsletterSubscriberType;

/**
 * @Route("/ajax", name="ajax_")
 */
class AjaxController extends AbstractController
{
    /**
     * @Route("/registerNewsletterSubcriber", name="register_newsletter_subscriber")
     */
    public function registerNewsletterSubcriber(
        Request $request,
        NewsletterSubscriberRepository $newsletterSubscriberRepository,
        TranslatorInterface $translator
    ): Response {
        $newsletterSubscriber = new NewsletterSubscriber();
        $formNewsletterSubscriber = $this->createForm(
            NewsletterSubscriberType::class,
            $newsletterSubscriber
        );

        $formNewsletterSubscriber->handleRequest($request);

        if ($formNewsletterSubscriber->isSubmitted() && $formNewsletterSubscriber->isValid()) {
            $newsletterSubscriber = $formNewsletterSubscriber->getData();

            $newsletterSubscribersInDb = $newsletterSubscriberRepository->findBy([
                'language' => $newsletterSubscriber->getLanguage(),
                'email' => $newsletterSubscriber->getEmail(),
            ]);

            if (count($newsletterSubscribersInDb)) {
                return $this->json([
                    'message' => $translator->trans(
                        'subscriber_already_exists',
                        [],
                        null,
                        $newsletterSubscriber->getLanguage()
                    ),
                ]);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletterSubscriber);
            $em->flush();

            return $this->json([
                'message' => $translator->trans(
                    'subscription_successful',
                    [],
                    null,
                    $newsletterSubscriber->getLanguage()
                ),
            ]);
        }
    }
}
