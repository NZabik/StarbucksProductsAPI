<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use App\Repository\ProductsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
* @Hateoas\Relation(
*      "self",
*      href = @Hateoas\Route(
*          "productDetail",
*          parameters = { "id" = "expr(object.getId())" },
*      ),
* )
*
* @Hateoas\Relation(
*      "delete",
*      href = @Hateoas\Route(
*          "deleteProduct",
*          parameters = { "id" = "expr(object.getId())" },
*      ),
*      exclusion = @Hateoas\Exclusion(excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
*      "update",
*      href = @Hateoas\Route(
*          "updateProduct",
*          parameters = { "id" = "expr(object.getId())" },
*      ),
*      exclusion = @Hateoas\Exclusion(excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/
#[ORM\Entity(repositoryClass: ProductsRepository::class)]
class Products
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'le nom est requis')]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'le prix est requis')]
    private ?float $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }
}
