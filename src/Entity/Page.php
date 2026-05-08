<?php

namespace AmzsCMS\PageBundle\Entity;

use AmzsCMS\ArticleBundle\Entity\Post;
use AmzsCMS\CoreBundle\Traits\Doctrine\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AmzsCMS\PageBundle\Repository\PageRepository")
 * @ORM\Table(name="amzs_page")
 * @ORM\HasLifecycleCallbacks
 *
 */
class Page
{
    use Timestampable;
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private  $id;


    /**
     * @ORM\Column(type="string", name="name", nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="AmzsCMS\ArticleBundle\Entity\Post", inversedBy="page", cascade={"persist"})
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id",nullable=true)
     */
    private $post;

    /**
     * @ORM\ManyToOne(targetEntity="AmzsCMS\PageBundle\Entity\Page", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id",nullable=true)
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="AmzsCMS\PageBundle\Entity\Page", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\Column(type="string", name="type", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", name="seo_url", nullable=true)
     */
    private $seoUrl;

    /**
     * @ORM\Column(type="string", name="css", nullable=true)
     */
    private $css;

    /**
     * @ORM\Column(type="text", name="custom_css", nullable=true)
     */
    private $customCss;


    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getSeoUrl(): ?string
    {
        return $this->seoUrl;
    }

    public function setSeoUrl(?string $seoUrl)
    {
        $this->seoUrl = $seoUrl;

        return $this;
    }

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function setCss(?string $css)
    {
        $this->css = $css;

        return $this;
    }

    public function getCustomCss(): ?string
    {
        return $this->customCss;
    }

    public function setCustomCss(?string $customCss)
    {
        $this->customCss = $customCss;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Page>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Page $child)
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Page $child)
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

}