<?php

namespace App\Entity;

use App\Entity\Traits\EntityTrait;
use App\Repository\MerchantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MerchantRepository::class)]
class Merchant
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $shortcode = null;

    /**
     * @var Collection<int, Email>
     */
    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'merchant')]
    private Collection $emails;

    public function __construct()
    {
        $this->code = uniqid();
        $this->createdAt = new \DateTimeImmutable();
        $this->emails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortcode(): ?string
    {
        return $this->shortcode;
    }

    public function setShortcode(string $shortcode): static
    {
        $this->shortcode = $shortcode;

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(Email $email): static
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setMerchant($this);
        }

        return $this;
    }

    public function removeEmail(Email $email): static
    {
        if ($this->emails->removeElement($email)) {
            // set the owning side to null (unless already changed)
            if ($email->getMerchant() === $this) {
                $email->setMerchant(null);
            }
        }

        return $this;
    }
}
