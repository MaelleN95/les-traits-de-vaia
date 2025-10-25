<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide.')]
    #[Assert\Length(max: 50, maxMessage: 'Le statut ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le total ne peut pas être vide.')]
    #[Assert\Positive(message: 'Le total doit être un nombre positif.')]
    #[Assert\Type(
        type: 'integer',
        message: 'Le total doit être un nombre entier.'
    )]
    private ?int $total = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de création ne peut pas être vide.')]
    #[Assert\Type(
        type: \DateTimeImmutable::class,
        message: 'La date de création doit être une date valide.'
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(
        type: \DateTimeImmutable::class,
        message: 'La date de mise à jour doit être une date valide.'
    )]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $address = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder')]
    private Collection $items;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(
        type: \DateTimeImmutable::class,
        message: 'La date de paiement doit être une date valide.'
    )]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCustomerOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCustomerOrder() === $this) {
                $item->setCustomerOrder(null);
            }
        }

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }
}
