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
    public function index(Request $request, BlogPostRepository $blogPostRepository, TranslatorInterface $translator)
    {
        $posts = $blogPostRepository->findPublishedPosts($request->getLocale());

        return $this->render('devAid/page-index.html.twig', [
            'posts' => $posts,
            'navigation' => [
                [
                    'li_class' => 'nav-item sr-only',
                    'a_class' => 'nav-link',
                    'href' => $this->generateUrl('index'),
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
                    'a_class' => 'nav-link',
                    'href' => $this->generateUrl('blog'),
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
                [
                    'li_class' => 'nav-item',
                    'a_class' => 'nav-link',
                    'href' => $this->generateUrl('contribute'),
                    'text' => $translator->trans('contribute'),
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

        return $this->render('devAid/page.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
        ]);
    }

    /**
     * @Route("/donate", name="donate")
     */
    public function donate(Request $request, TranslatorInterface $translator)
    {
        $title = $translator->trans('donate_title');
        $content = $this->renderView("content/donate-{$request->getLocale()}.html.twig");

        return $this->render('devAid/page-donate.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
        ]);
    }

    /**
     * @Route("/doc/{project}/{branch}/{resource}.{extension}", name="doc", requirements={"resource"=".+"})
     */
    public function doc(string $project, string $branch, string $resource, string $extension, MarkdownParserInterface $parser, Request $request)
    {
        $locale = $request->getLocale();

        if ('html' == $extension) {
            $extension = 'md';
        }

        $filename = __DIR__."/../../doc/{$project}/{$branch}/{$locale}/{$resource}.{$extension}";

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

            return $this->render('devAid/page-doc.html.twig', [
                'content' => $content,
                'contentTitle' => $contentTitle,
                'pageTitle' => $contentTitle,
                'meta_description' => "{$contentTitle}",
                'menu' => $menu,
                'donate' => true,
                'url_doc' => "https://github.com/thenlabs/doc/edit/master/{$project}/{$branch}/{$locale}/{$fileInfo['filename']}.{$fileInfo['extension']}",
            ]);
        } else {
            return new BinaryFileResponse($filename);
        }
    }

    /**
     * @Route("/blog", name="blog")
     */
    public function blog(Request $request, BlogPostRepository $blogPostRepository)
    {
        return $this->render('devAid/page-blog.html.twig', [
            'posts' => $blogPostRepository->findPublishedPosts($request->getLocale()),
            'donate' => true,
        ]);
    }

    /**
     * @Route("/blog/{slug}.html", name="blogPost")
     * @ParamConverter("post", class="App\Entity\BlogPost")
     */
    public function blogPost(BlogPost $blogPost, MarkdownParserInterface $parser, TranslatorInterface $translator)
    {
        if (!$blogPost->isPublic()) {
            throw new NotFoundHttpException();
        }

        $parser->code_class_prefix = 'language-';

        $content = $parser->transformMarkdown($blogPost->getContent());
        $content = HtmlPageCrawler::create('<div class="docs-content">'.$content.'</div>');
        $content->filter('pre > code:not([class])')->addClass('language-markup');
        $content->filter('img:not([class])')->addClass('img-fluid');

        $title = $blogPost->getTitle();
        $menu = $this->getMenu($content);
        $ogDescription = substr($blogPost->getAbstract(), 0, 195);

        $translationsMenu = [];
        $publicationDateStr = $translator->trans('publication_date');

        foreach ($blogPost->getTranslations() as $translation) {
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

        return $this->render('devAid/page-blog-post.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'blogPost' => $blogPost,
            'ogImage' => $blogPost->getOgImage(),
            'pageTitle' => $title,
            'meta_description' => $ogDescription,
            'ogDescription' => $ogDescription,
            'menu' => $menu,
            'donate' => true,
            'comments' => true,
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
            $id = str_replace('Á', 'A', $id);
            $id = str_replace('É', 'E', $id);
            $id = str_replace('Í', 'I', $id);
            $id = str_replace('Ó', 'O', $id);
            $id = str_replace('Ú', 'U', $id);
            $id = strtolower($id);
            $h2->setAttribute('id', $id);

            $menu[] = ['id' => $id, 'text' => $linkText];
        });

        return $menu;
    }

    /**
     * @Route("/contribute", name="contribute")
     */
    public function contribute(Request $request, MarkdownParserInterface $parser, TranslatorInterface $translator)
    {
        $title = $translator->trans('contribute');

        return $this->render('devAid/page-contribute.html.twig', [
            'contentTitle' => $title,
            'pageTitle' => $title,
        ]);
    }
}
