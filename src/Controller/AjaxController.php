<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        TranslatorInterface $translator,
        MailerInterface $mailer
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

            // Send the email
            $email = (new Email())
                ->to($newsletterSubscriber->getEmail())
                ->subject(
                    $translator->trans(
                        'verify_your_email',
                        [],
                        null,
                        $newsletterSubscriber->getLanguage()
                    )
                )
                ->html(
                    $translator->trans(
                        'verify_your_email_content',
                        ['%URL%' => $this->generateUrl('verify_email', [], UrlGeneratorInterface::ABSOLUTE_URL).'?token='.$newsletterSubscriber->getToken()],
                        null,
                        $newsletterSubscriber->getLanguage()
                    )
                )
            ;

            $sentMessage = $mailer->send($email);

            $newsletterSubscriber->setEmailSentLog($sentMessage->toString());

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
