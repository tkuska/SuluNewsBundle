<?php

declare(strict_types=1);

namespace Manuxi\SuluNewsBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Manuxi\SuluNewsBundle\Entity\Interfaces\AuditableTranslatableInterface;
use Manuxi\SuluNewsBundle\Entity\Traits\AuditableTranslatableTrait;
use Manuxi\SuluNewsBundle\Entity\Traits\TypeTrait;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_news")
 * @ORM\Entity(repositoryClass="NewsRepository")
 */
class News implements AuditableTranslatableInterface
{
    public const RESOURCE_KEY = 'news';
    public const FORM_KEY = 'news_details';
    public const LIST_KEY = 'news';
    public const SECURITY_CONTEXT = 'sulu.news.news';

    use AuditableTranslatableTrait;
    use TypeTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity="NewsSeo", mappedBy="news", cascade={"persist", "remove"})
     * @Serializer\Exclude
     */
    private ?NewsSeo $newsSeo = null;

    /**
     * @ORM\OneToOne(targetEntity="NewsExcerpt", mappedBy="news", cascade={"persist", "remove"})
     * @Serializer\Exclude
     */
    private ?NewsExcerpt $newsExcerpt = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $images = null;

    /**
     * @ORM\OneToMany(targetEntity="NewsTranslation", mappedBy="news", cascade={"ALL"}, indexBy="locale", fetch="EXTRA_LAZY")
     * @Serializer\Exclude
     */
    private Collection $translations;

    private string $locale = 'en';

    private array $ext = [];

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->initExt();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Serializer\VirtualProperty(name="title")
     */
    public function getTitle(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getTitle();
    }

    public function setTitle(string $title): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setTitle($title);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="subtitle")
     */
    public function getSubtitle(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getSubtitle();
    }

    public function setSubtitle(?string $subtitle): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setSubtitle($subtitle);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="summary")
     */
    public function getSummary(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getSummary();
    }

    public function setSummary(?string $summary): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setSummary($summary);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="text")
     */
    public function getText(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getText();
    }

    public function setText(string $text): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setText($text);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="footer")
     */
    public function getFooter(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getFooter();
    }

    public function setFooter(?string $footer): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setFooter($footer);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="route_path")
     */
    public function getRoutePath(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getRoutePath();
    }

    public function getRoute(): ?string
    {
        return $this->getRoutePath();
    }

    public function setRoutePath(string $routePath): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setRoutePath($routePath);
        return $this;
    }

    public function getImage(): ?MediaInterface
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getImage();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("image")
     */
    public function getImageData(): ?array
    {
        $translation = $this->getTranslation($this->locale);

        if (!$translation) {
            return null;
        }
        if ($image = $translation->getImage()) {
            return [
                'id' => $image->getId(),
            ];
        }

        return null;

    }

    public function setImage(?MediaInterface $image): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setImage($image);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="url")
     */
    public function getUrl(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getUrl();
    }

    public function setUrl(string $url): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setUrl($url);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="pdf")
     */
    public function getPdf(): ?MediaInterface
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getPdf();
    }

    public function setPdf(?MediaInterface $pdf): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setPdf($pdf);
        return $this;
    }

    public function getNewsSeo(): NewsSeo
    {
        if (!$this->newsSeo instanceof NewsSeo) {
            $this->newsSeo = new NewsSeo();
            $this->newsSeo->setNews($this);
        }

        return $this->newsSeo;
    }

    public function setNewsSeo(?NewsSeo $newsSeo): self
    {
        $this->newsSeo = $newsSeo;
        return $this;
    }

    public function getNewsExcerpt(): NewsExcerpt
    {
        if (!$this->newsExcerpt instanceof NewsExcerpt) {
            $this->newsExcerpt = new NewsExcerpt();
            $this->newsExcerpt->setNews($this);
        }

        return $this->newsExcerpt;
    }

    public function setNewsExcerpt(?NewsExcerpt $newsExcerpt): self
    {
        $this->newsExcerpt = $newsExcerpt;
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="ext")
     */
    public function getExt(): array
    {
        return $this->ext;
    }

    public function setExt(array $ext): self
    {
        $this->ext = $ext;
        return $this;
    }

    public function addExt(string $key, $value): self
    {
        $this->ext[$key] = $value;
        return $this;
    }

    public function hasExt(string $key): bool
    {
        return \array_key_exists($key, $this->ext);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        $this->propagateLocale($locale);
        return $this;
    }

    /**
     * @return NewsTranslation[]
     */
    public function getTranslations(): array
    {
        return $this->translations->toArray();
    }

    protected function getTranslation(string $locale): ?NewsTranslation
    {
        if (!$this->translations->containsKey($locale)) {
            return null;
        }

        return $this->translations->get($locale);
    }

    protected function createTranslation(string $locale): NewsTranslation
    {
        $translation = new NewsTranslation($this, $locale);
        $this->translations->set($locale, $translation);
        return $translation;
    }

    private function propagateLocale(string $locale): self
    {
        $newsSeo = $this->getNewsSeo();
        $newsSeo->setLocale($locale);
        $newsExcerpt = $this->getNewsExcerpt();
        $newsExcerpt->setLocale($locale);
        $this->initExt();
        return $this;
    }

    private function initExt(): self
    {
        if (!$this->hasExt('seo')) {
            $this->addExt('seo', $this->getNewsSeo());
        }
        if (!$this->hasExt('excerpt')) {
            $this->addExt('excerpt', $this->getNewsExcerpt());
        }

        return $this;
    }

    /**
     * @Serializer\VirtualProperty("availableLocales")
     */
    public function getAvailableLocales(): array
    {
        return \array_values($this->translations->getKeys());
    }

    /**
     * @todo implement opject cloning/copy
     * @return $this|null
     */
    public function copy(): ?static
    {
        return null;
    }

    public function copyToLocale(string $locale): self
    {
        if ($currentTranslation = $this->getTranslation($this->getLocale())) {
           $newTranslation = clone $currentTranslation;
           $newTranslation->setLocale($locale);
           $this->translations->set($locale, $newTranslation);

           //copy ext also...
           foreach($this->ext as $translatable) {
               $translatable->copyToLocale($locale);
           }

           $this->setLocale($locale);
        }
        return $this;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @Serializer\VirtualProperty("published")
     */
    public function getPublished(): ?bool
    {
        return $this->isPublished();
    }

    public function isPublished(): ?bool
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->isPublished();
    }

    public function setPublished(bool $published): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setPublished($published);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="published_at")
     */
    public function getPublishedAt(): ?DateTime
    {
        $translation = $this->getTranslation($this->locale);
        if(!$translation) {
            return null;
        }
        return $translation->getPublishedAt();
    }

    public function setPublishedAt(?DateTime $date): self
    {
        $translation = $this->getTranslation($this->locale);
        if(!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setPublishedAt($date);
        return $this;
    }

}
