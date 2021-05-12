<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="newsletter_subscriber",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="email_language",
 *            columns={"email", "language"})
 *    }
 * )
 * @ORM\Entity(repositoryClass=NewsletterSubscriberRepository::class)
 */
class NewsletterSubscriber
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $language;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $verified;

    public function __construct()
    {
        $this->created = new \DateTime;
        $this->token = bin2hex(random_bytes(64));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getVerified(): ?\DateTimeInterface
    {
        return $this->verified;
    }

    public function setVerified(?\DateTimeInterface $verified): self
    {
        $this->verified = $verified;

        return $this;
    }
}
