<?php

namespace App\EventSubscriber;

use App\Repository\BlogPostRepository;
use App\Controller\MainController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class SitemapSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var BlogPostRepository
     */
    private $blogPostRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param BlogPostRepository    $blogPostRepository
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, BlogPostRepository $blogPostRepository, TranslatorInterface $translator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->blogPostRepository = $blogPostRepository;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'populate',
        ];
    }

    /**
     * @param SitemapPopulateEvent $event
     */
    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerBlogPostsUrls($event->getUrlContainer());
        $this->registerProjectDocsUrls($event->getUrlContainer());
    }

    /**
     * @param UrlContainerInterface $urls
     */
    public function registerBlogPostsUrls(UrlContainerInterface $urls): void
    {
        $posts = $this->blogPostRepository->findBy(['public' => true]);

        foreach ($posts as $post) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'blogPost',
                        ['slug' => $post->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'blog'
            );
        }
    }

    /**
     * @param UrlContainerInterface $urls
     */
    public function registerProjectDocsUrls(UrlContainerInterface $urls): void
    {
        $projects = MainController::getProjects($this->translator, $this->urlGenerator);

        foreach ($projects as $project) {
            if (true === $project['sitemap']) {
                $urls->addUrl(
                    new UrlConcrete($project['url']),
                    'doc'
                );
            }
        }
    }
}