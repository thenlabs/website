<?php

namespace App\Controller;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Wa72\HtmlPageDom\HtmlPageCrawler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Entity\BlogPost;
use App\Entity\Page;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('devAid/page-index.html.twig', [
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
            ],
        ]);
    }

    /**
     * @Route("/docs/{project}/{branch}/{language}/{resource}.{extension}", name="docs", requirements={"resource"=".+"})
     */
    public function docs(string $project, string $branch, string $language, string $resource, string $extension, MarkdownParserInterface $parser)
    {
        if ($extension == 'html') {
            $extension = 'md';
        }

        $filename = __DIR__."/../../docs/{$project}/{$branch}/docs/{$language}/{$resource}.{$extension}";

        if (! file_exists($filename)) {
            throw new NotFoundHttpException;
        }

        $fileInfo = pathInfo($filename);
        if ($fileInfo['extension'] == 'md') {
            $parser->code_class_prefix = 'language-';
            $parser->url_filter_func = function ($url) {
                return preg_replace('/\.md$/', '.html', $url);
            };

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
            $content->filter('img:not([class])')->addClass('img-fluid');

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
                $id = str_replace('Í', 'I', $id);
                $id = strtolower($id);
                $h2->setAttribute('id', $id);

                $menu[] = ['id' => $id, 'text' => $linkText];
            });

            // $template = empty($menu) ?
            //     'devAid/page-docs-without-menu.html.twig' :
            //     'devAid/page-docs.html.twig'
            // ;

            $template = 'devAid/page-docs.html.twig';

            return $this->render($template, [
                'content' => $content,
                'contentTitle' => $contentTitle,
                'pageTitle' => $contentTitle,
                'meta_description' => "{$contentTitle} | ThenLabs",
                'menu' => $menu,
            ]);
        } else {
            return new BinaryFileResponse($filename);
        }
    }

    /**
     * @Route("/blog", name="blog")
     */
    public function blog()
    {
        return $this->render('devAid/page-blog.html.twig');
    }

    /**
     * @Route("/blog/{slug}", name="blogPost")
     * @ParamConverter("post", class="App\Entity\BlogPost")
     */
    public function blogPost(BlogPost $post, MarkdownParserInterface $parser)
    {
        return $this->getResponseForMarkdownContent($post, $parser);
    }

    /**
     * @Route("/page/{slug}", name="page")
     * @ParamConverter("page", class="App\Entity\Page")
     */
    public function page(Page $page, MarkdownParserInterface $parser)
    {
        return $this->getResponseForMarkdownContent($page, $parser);
    }

    public function getResponseForMarkdownContent($entity, MarkdownParserInterface $parser)
    {
        $parser->code_class_prefix = 'language-';

        $content = $parser->transformMarkdown($entity->getContent());
        $content = HtmlPageCrawler::create('<div class="docs-content">'.$content.'</div>');
        $content->filter('pre > code:not([class])')->addClass('language-markup');
        $content->filter('img:not([class])')->addClass('img-fluid');

        $title = $entity->getTitle();

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
            $id = str_replace('Í', 'I', $id);
            $id = strtolower($id);
            $h2->setAttribute('id', $id);

            $menu[] = ['id' => $id, 'text' => $linkText];
        });

        return $this->render('devAid/page-docs.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
            'meta_description' => "{$title} | ThenLabs",
            'menu' => $menu,
        ]);
    }
}
