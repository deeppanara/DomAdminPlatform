<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Category.
 *
 * @author MacFJA
 *
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
class Category
{
    /**
     * The identifier of the category.
     *
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id = null;

    /**
     * The category name.
     *
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Product in the category.
     *
     * @var Product[]
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="categories")
     **/
    protected $products;

    /**
     * The category parent.
     *
     * @var Category
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     **/
    protected $parent;

    private $lft;

    private $rgt;

    private $lvl;

    private $slug;

    private $title;

    private $description;

    private $h1Title;

    private $metaDescription;

    private $synonyms;

    private $treeRoot;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->listingType = new ArrayCollection();
        $this->page = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Get the id of the category.
     * Return null if the category is new and not saved.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the name of the category.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the category.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the parent category.
     *
     * @param Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the parent category.
     *
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return all product associated to the category.
     *
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set all products in the category.
     *
     * @param Product[] $products
     */
    public function setProducts($products)
    {
        $this->products->clear();
        $this->products = new ArrayCollection($products);
    }

    /**
     * Add a product in the category.
     *
     * @param $product Product The product to associate
     */
    public function addProduct($product)
    {
        if ($this->products->contains($product)) {
            return;
        }

        $this->products->add($product);
        $product->addCategory($this);
    }

    /**
     * @param Product $product
     */
    public function removeProduct($product)
    {
        if (!$this->products->contains($product)) {
            return;
        }

        $this->products->removeElement($product);
        $product->removeCategory($this);
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getH1Title(): ?string
    {
        return $this->h1Title;
    }

    public function setH1Title(?string $h1Title): self
    {
        $this->h1Title = $h1Title;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getSynonyms(): ?string
    {
        return $this->synonyms;
    }

    public function setSynonyms(?string $synonyms): self
    {
        $this->synonyms = $synonyms;

        return $this;
    }

    public function getTreeRoot(): ?self
    {
        return $this->treeRoot;
    }

    public function setTreeRoot(?self $treeRoot): self
    {
        $this->treeRoot = $treeRoot;

        return $this;
    }

    /**
     * @return Collection|ListingType[]
     */
    public function getListingType(): Collection
    {
        return $this->listingType;
    }

//    public function addListingType(ListingType $listingType): self
//    {
//        if (!$this->listingType->contains($listingType)) {
//            $this->listingType[] = $listingType;
//        }
//
//        return $this;
//    }
//
//    public function removeListingType(ListingType $listingType): self
//    {
//        if ($this->listingType->contains($listingType)) {
//            $this->listingType->removeElement($listingType);
//        }
//
//        return $this;
//    }

//    /**
//     * @return Collection|Page[]
//     */
//    public function getPage(): Collection
//    {
//        return $this->page;
//    }
//
//    public function addPage(Page $page): self
//    {
//        if (!$this->page->contains($page)) {
//            $this->page[] = $page;
//            $page->addCategory($this);
//        }
//
//        return $this;
//    }
//
//    public function removePage(Page $page): self
//    {
//        if ($this->page->contains($page)) {
//            $this->page->removeElement($page);
//            $page->removeCategory($this);
//        }
//
//        return $this;
//    }
}
