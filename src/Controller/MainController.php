<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('devAid/page-index.html.twig', [
            'twitter_username' => $this->getParameter('twitter_username'),
            'telegram_url' => $this->getParameter('telegram_url'),
            'facebook_url' => $this->getParameter('facebook_url'),
            'twitter_url' => $this->getParameter('twitter_url'),
            'nav_items' => [
                [
                    'li_class' => 'nav-item sr-only',
                    'a_class'  => 'nav-link scrollto',
                    'href'     => '#promo',
                    'text'     => 'Inicio',
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link scrollto',
                    'href'     => '#projects',
                    'text'     => 'Proyectos',
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link scrollto',
                    'href'     => '#socials',
                    'text'     => 'Redes Sociales',
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link scrollto',
                    'href'     => '#contact',
                    'text'     => 'Contacto',
                ],
            ],
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('devAid/page-about.html.twig', [
            'nav_items' => []
        ]);
    }
}
