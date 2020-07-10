<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\BlogPost;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $abstract = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse';
        $content = <<<TEXT
        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
        proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
        proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
        TEXT;

        $admin = new User();
        $admin->setEmail('admin@thenlabs.org');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->encoder->encodePassword($admin, 'admin123'));

        $post1 = new BlogPost();
        $post1->setTitle('My Title 1');
        $post1->setSlug('my-slug-1');
        $post1->setLanguage('en');
        $post1->setContent($content);
        $post1->setPublic(true);
        $post1->setAbstract($abstract);

        $post2 = new BlogPost();
        $post2->setTitle('My Title 2');
        $post2->setSlug('my-slug-2');
        $post2->setLanguage('en');
        $post2->setContent($content);
        $post2->setPublic(true);
        $post2->setAbstract($abstract);

        $manager->persist($post1);
        $manager->persist($post2);
        $manager->persist($admin);
        $manager->flush();
    }
}
