<?php

namespace App\Entity;

use App\Repository\CookRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CookRequestRepository::class)]
#[ORM\Table(name: "cook_requests")]
class CookRequest
{
    public const STATUS_QUEUED = "queued";
    public const STATUS_DONE = "done";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $title = "";

    #[ORM\Column(type: "string", length: 16)]
    private string $status = self::STATUS_QUEUED;

    #[ORM\Column(type: "integer")]
    private int $position = 0;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "cookRequests")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $owner = null;

    #[ORM\OneToMany(targetEntity: CookRequestEntry::class, mappedBy: "cookRequest", cascade: ["persist", "remove"], orphanRemoval: true)]
    #[ORM\OrderBy(["position" => "ASC"])]
    private Collection $entries;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function markDone(\DateTimeImmutable $completedAt): self
    {
        $this->status = self::STATUS_DONE;
        $this->completedAt = $completedAt;
        return $this;
    }

    public function reopen(int $position): self
    {
        $this->status = self::STATUS_QUEUED;
        $this->completedAt = null;
        $this->position = $position;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(CookRequestEntry $entry): self
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setCookRequest($this);
            $entry->setPosition($this->entries->count());
        }
        return $this;
    }

    public function removeEntry(CookRequestEntry $entry): self
    {
        $this->entries->removeElement($entry);
        return $this;
    }
}
