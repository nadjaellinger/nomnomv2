<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "cook_request_entries")]
#[ORM\UniqueConstraint(name: "uniq_cook_request_recipe", columns: ["cook_request_id", "recipe_id"])]
class CookRequestEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CookRequest::class, inversedBy: "entries")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?CookRequest $cookRequest = null;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Recipe $recipe = null;

    #[ORM\Column(type: "integer")]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCookRequest(): ?CookRequest
    {
        return $this->cookRequest;
    }

    public function setCookRequest(?CookRequest $cookRequest): self
    {
        $this->cookRequest = $cookRequest;
        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): self
    {
        $this->recipe = $recipe;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }
}
