<?php

namespace App\Entity\Common;

use Doctrine\Common\Collections\Collection;

trait BlogPostAndPageTrait
{
    use SlugTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $language;

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(self $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->addTranslation($this);
        }

        return $this;
    }

    public function removeTranslation(self $translation): self
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
            $translation->removeTranslation($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }
}
