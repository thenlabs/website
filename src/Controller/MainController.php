<?php

namespace App\Controller;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Wa72\HtmlPageDom\HtmlPageCrawler;

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
            'navigation' => [
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
                    'text'     => 'Contactar',
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link',
                    'href'     => $this->generateUrl('faq'),
                    'text'     => 'FAQ',
                ],
            ],
        ]);
    }

    /**
     * @Route("/faq", name="faq")
     */
    public function faq()
    {
        return $this->render('devAid/page-faq.html.twig', [
            'navigation' => [
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link',
                    'href'     => $this->generateUrl('index'),
                    'text'     => 'Inicio',
                ],
            ]
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('devAid/page-about.html.twig', [
            'navigation' => [
                [
                    'li_class' => 'nav-item',
                    'a_class'  => 'nav-link',
                    'href'     => $this->generateUrl('index'),
                    'text'     => 'Inicio',
                ],
            ]
        ]);
    }

    /**
     * @Route("/docs", name="docs")
     */
    public function docs(MarkdownParserInterface $parser)
    {
        $navigation = [];

        $parser->code_class_prefix = 'language-';

        $content = $parser->transformMarkdown(
            file_get_contents(__DIR__.'/../../docs/components/master/docs/es/index.md')
        );

        /**
         * The content should be wrapped because the crawler has bug when deleting root nodes.
         * @see https://github.com/wasinger/htmlpagedom/issues/25#issuecomment-507515271
         */
        $content = HtmlPageCrawler::create('<div class="docs-content">'.$content.'</div>');

        $contentTitleElement = $content->filter('h1');
        $contentTitle = $contentTitleElement->getInnerHtml();

        $contentTitleElement->remove();

        $content->filter('pre > code:not([class])')->addClass('language-markup');

        return $this->render('devAid/page-docs.html.twig', [
            'navigation' => [],
            'content' => $content,
            'contentTitle' => $contentTitle,
        ]);
    }
}
