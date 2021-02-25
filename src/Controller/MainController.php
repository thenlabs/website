<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Page;
use App\Repository\BlogPostRepository;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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
     * @Route("/", name="index", options={"sitemap" = true})
     */
    public function index(Request $request, BlogPostRepository $blogPostRepository, TranslatorInterface $translator, RouterInterface $router)
    {
        $posts = $blogPostRepository->findPublishedPosts($request->getLocale());

        return $this->render('devAid/page-index.html.twig', [
            'posts' => $posts,
            'hrefLangs' => $this->getHrefLangs('index', []),
            'projects' => self::getProjects($translator, $router),
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
     * @Route("/about", name="about", options={"sitemap" = true})
     */
    public function about(Request $request, TranslatorInterface $translator)
    {
        $content = $this->renderView("content/about-{$request->getLocale()}.html.twig");
        $title = $translator->trans('about_title');

        return $this->render('devAid/page.html.twig', [
            'content' => $content,
            'contentTitle' => $title,
            'pageTitle' => $title,
            'hrefLangs' => $this->getHrefLangs('about', []),
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
                'hrefLangs' => $this->getHrefLangs('doc', compact('project', 'branch', 'resource', 'extension')),
            ]);
        } else {
            return new BinaryFileResponse($filename);
        }
    }

    /**
     * @Route("/blog", name="blog", options={"sitemap" = true})
     */
    public function blog(Request $request, BlogPostRepository $blogPostRepository)
    {
        return $this->render('devAid/page-blog.html.twig', [
            'posts' => $blogPostRepository->findPublishedPosts($request->getLocale()),
            'donate' => true,
            'hrefLangs' => $this->getHrefLangs('blog', []),
        ]);
    }

    /**
     * @Route("/blog/{slug}.html", name="blogPost")
     * @ParamConverter("post", class="App\Entity\BlogPost")
     */
    public function blogPost(BlogPost $blogPost, Request $request, MarkdownParserInterface $parser, TranslatorInterface $translator)
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

        $languages = [$request->getLocale()];

        foreach ($blogPost->getTranslations() as $translation) {
            $text = '';
            $language = $translation->getLanguage();
            $languages[] = $language;

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
            'hrefLangs' => $this->getHrefLangs('blogPost', ['slug' => $blogPost->getSlug()], $languages),
        ]);
    }

    /**
     * @Route("/contribute", name="contribute", options={"sitemap" = true})
     */
    public function contribute(Request $request, MarkdownParserInterface $parser, TranslatorInterface $translator)
    {
        $title = $translator->trans('contribute');
        $locale = $request->getLocale();

        return $this->render("devAid/page-contribute-{$locale}.html.twig", [
            'contentTitle' => $title,
            'pageTitle' => $title,
            'hrefLangs' => $this->getHrefLangs('contribute', []),
        ]);
    }

    private function getMenu($content)
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

    private function getHrefLangs(string $routeName, array $args, array $languages = ['en', 'es']): array
    {
        $result = [];

        foreach ($languages as $lang) {
            $arguments = array_merge($args, ['_locale' => $lang]);

            $result[] = [
                'lang' => $lang,
                'url' => $this->generateUrl($routeName, $arguments, UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $result;
    }

    public static function getProjects(TranslatorInterface $translator, RouterInterface $router): array
    {
        return [
            'stratus-php' => [
                'name' => 'StratusPHP',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                    ['class' => 'success', 'text' => 'Framework'],
                    ['class' => 'danger', 'text' => $translator->trans('new')],
                ],
                'description' => $translator->trans('project.description.stratus_php'),
                'url' => $router->generate('doc', ['project' => 'stratus-php', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'composed-views' => [
                'name' => 'ComposedViews',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                    ['class' => 'success', 'text' => 'Framework'],
                ],
                'description' => $translator->trans('project.description.composed_views'),
                'url' => $router->generate('doc', ['project' => 'composed-views', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'pyramidal-tests' => [
                'name' => 'PyramidalTests',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                    ['class' => 'success', 'text' => 'Framework'],
                    ['class' => 'warning', 'text' => $translator->trans('Previous')],
                ],
                'description' => $translator->trans('project.description.pyramidal_tests'),
                'url' => $router->generate('doc', ['project' => 'pyramidal-tests', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'components' => [
                'name' => 'Components',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                ],
                'description' => $translator->trans('project.description.components'),
                'url' => $router->generate('doc', ['project' => 'components', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'class-builder' => [
                'name' => 'ClassBuilder',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                ],
                'description' => $translator->trans('project.description.class_builder'),
                'url' => $router->generate('doc', ['project' => 'class-builder', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'cli' => [
                'name' => 'CLI',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                ],
                'description' => $translator->trans('project.description.then_cli'),
                'url' => $router->generate('doc', ['project' => 'cli', 'branch' => 'master', 'resource' => 'index', 'extension' => 'html']),
            ],

            'glue-php' => [
                'name' => 'GluePHP',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                    ['class' => 'success', 'text' => 'Framework'],
                    ['class' => 'warning', 'text' => $translator->trans('Previous')],
                    ['class' => 'warning', 'text' => $translator->trans('Abandoned')],
                ],
                'description' => $translator->trans('project.description.glue_php'),
                'url' => 'https://gluephp.readthedocs.io/es/latest/index.html',
            ],

            'website' => [
                'name' => 'Website',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                ],
                'description' => $translator->trans('project.description.website'),
                'url' => 'https://github.com/thenlabs/website',
            ],

            'http-server' => [
                'name' => 'HttpServer',
                'tags' => [
                    ['class' => 'info', 'text' => 'PHP'],
                ],
                'description' => $translator->trans('project.description.http_server'),
                'url' => 'https://github.com/thenlabs/http-server',
            ],
        ];
    }
}
