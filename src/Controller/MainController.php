<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Page;
use App\Repository\BlogPostRepository;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * @Route(
 *     "/{_locale}",
 *     requirements={
 *         "_locale": "en|es",
 *     }
 * )
 */
class MainController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request, BlogPostRepository $postRepository, TranslatorInterface $translator)
    {
        $posts = $postRepository->findPostsForCarousel($request->getLocale());

        return $this->render('devAid/page-index.html.twig', [
            'posts' => $posts,
            'navigation' => [
                [
                    'li_class' => 'nav-item sr-only',
                    'a_class' => 'nav-link scrollto',
                    'href' => '#promo',
                    'text' => $translator->trans('Home'),
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class' => 'nav-link scrollto',
                    'href' => '#projects',
                    'text' => $translator->trans('Projects'),
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class' => 'nav-link scrollto',
                    'href' => '#blog',
                    'text' => $translator->trans('Blog'),
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class' => 'nav-link scrollto',
                    'href' => '#socials',
                    'text' => $translator->trans('Social Networks'),
                ],
                [
                    'li_class' => 'nav-item',
                    'a_class' => 'nav-link scrollto',
                    'href' => '#contact',
                    'text' => $translator->trans('Contact'),
                ],
            ],
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about(Request $request, TranslatorInterface $translator)
    {
        $content = $this->renderView("content/about-{$request->getLocale()}.html.twig");
        $title = $translator->trans('about_title');

        return $this->render('devAid/page-docs.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
            // 'menu' => [],
            // 'translations_menu' => [],
        ]);
    }

    /**
     * @Route("/doc/{project}/{branch}/{resource}.{extension}", name="doc", requirements={"resource"=".+"})
     */
    public function doc(string $project, string $branch, string $resource, string $extension, MarkdownParserInterface $parser, Request $request)
    {
        $language = $request->getLocale();

        if ('html' == $extension) {
            $extension = 'md';
        }

        $filename = __DIR__."/../../doc/{$project}/{$branch}/{$language}/{$resource}.{$extension}";

        if (!file_exists($filename)) {
            throw new NotFoundHttpException();
        }

        $fileInfo = pathinfo($filename);
        if ('md' == $fileInfo['extension']) {
            $parser->code_class_prefix = 'language-';
            $parser->url_filter_func = function ($url) {
                return preg_replace('/\.md$/', '.html', $url);
            };

            $content = $parser->transformMarkdown(file_get_contents($filename));

            /**
             * The content should be wrapped because the crawler has bug when deleting root nodes.
             *
             * @see https://github.com/wasinger/htmlpagedom/issues/25#issuecomment-507515271
             */
            $content = HtmlPageCrawler::create('<div class="docs-content">'.$content.'</div>');

            $contentTitleElement = $content->filter('h1');
            $contentTitle = $contentTitleElement->getInnerHtml();

            $contentTitleElement->remove();

            $content->filter('pre > code:not([class])')->addClass('language-markup');
            $content->filter('img:not([class])')->addClass('img-fluid');

            $menu = $this->getMenu($content);

            return $this->render('devAid/page-docs.html.twig', [
                'content' => $content,
                'contentTitle' => $contentTitle,
                'pageTitle' => $contentTitle,
                'meta_description' => "{$contentTitle}",
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
        return new RedirectResponse(
            $this->generateUrl('index').'#blog'
        );
    }

    /**
     * @Route("/blog/{slug}.html", name="blogPost")
     * @ParamConverter("post", class="App\Entity\BlogPost")
     */
    public function blogPost(BlogPost $post, MarkdownParserInterface $parser)
    {
        if (!$post->isPublic()) {
            throw new NotFoundHttpException();
        }

        return $this->getResponseForMarkdownContent($post, $parser);
    }

    /**
     * @Route("/page/{slug}.html", name="page")
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
        $menu = $this->getMenu($content);

        $ogDescription = $entity instanceof BlogPost ?
            substr($entity->getAbstract(), 0, 195) :
            ''
        ;

        $translationsMenu = [];

        if ($entity instanceof BlogPost) {
            $date = $entity->getCreated()->format('Y-m-d');
            $url = $this->generateUrl('blogPost', ['slug' => $entity->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

            $content->append(<<<HTML
                <p class="text-center" style="margin-top:25px">
                    <small><i class="far fa-clock"></i> Fecha de publicación: {$date}</small>
                    <div class="fb-share-button" data-href="{$url}" data-layout="button_count"></div>
                </p>
            HTML);

            foreach ($entity->getTranslations() as $translation) {
                $text = '';
                $language = $translation->getLanguage();

                switch ($language) {
                    case 'es':
                        $text = 'Español';
                        break;

                    case 'en':
                        $text = 'English';
                        break;

                    default:
                        $text = '???';
                        break;
                }

                $url = $this->generateUrl(
                    'blogPost',
                    ['slug' => $translation->getSlug(), '_locale' => $language],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $translationsMenu[] = compact('text', 'url', 'language');
            }
        }

        return $this->render('devAid/page-docs.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
            'meta_description' => "{$title} | ThenLabs",
            'ogDescription' => $ogDescription,
            'menu' => $menu,
            'translations_menu' => $translationsMenu,
        ]);
    }

    public function getMenu($content)
    {
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

        return $menu;
    }
}
