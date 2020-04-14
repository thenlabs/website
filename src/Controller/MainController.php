<?php

namespace App\Controller;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @Route("/docs/{project}/{branch}/{language}/{document}.html", name="docs")
     */
    public function docs(string $project, string $branch, string $language, string $document, MarkdownParserInterface $parser)
    {
        $navigation = [];

        $parser->code_class_prefix = 'language-';

        define('DOCS_DIR', __DIR__.'/../../docs');

        $filename = DOCS_DIR."/{$project}/{$branch}/docs/{$language}/{$document}.md";
        if (! file_exists($filename)) {
            throw new NotFoundHttpException;
        }

        $content = $parser->transformMarkdown(file_get_contents($filename));

        /**
         * The content should be wrapped because the crawler has bug when deleting root nodes.
         * @see https://github.com/wasinger/htmlpagedom/issues/25#issuecomment-507515271
         */
        $content = HtmlPageCrawler::create('<div class="docs-content">'.$content.'</div>');

        $contentTitleElement = $content->filter('h1');
        $contentTitle = $contentTitleElement->getInnerHtml();

        $contentTitleElement->remove();

        $content->filter('pre > code:not([class])')->addClass('language-markup');

        $menu = [];
        $content->filter('h2')->each(function ($h2, $i) use (&$menu) {
            $text = $h2->getInnerHtml();
            $n = $i + 1;

            $linkText = "{$n}. {$text}";
            $h2->setInnerHtml($linkText);

            $id = str_replace('.', '', 'l-'.$linkText);
            $id = str_replace(' ', '-', $id);
            $id = str_replace('á', 'a', $id);
            $id = str_replace('é', 'e', $id);
            $id = str_replace('í', 'i', $id);
            $id = str_replace('ó', 'o', $id);
            $id = str_replace('ú', 'u', $id);
            $id = strtolower($id);
            $h2->setAttribute('id', $id);

            $menu[] = ['id' => $id, 'text' => $linkText];
        });

        return $this->render('devAid/page-docs.html.twig', [
            'navigation' => [],
            'content' => $content,
            'contentTitle' => $contentTitle,
            'menu' => $menu,
        ]);
    }
}
