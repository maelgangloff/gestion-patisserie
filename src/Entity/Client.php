<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $nom;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $prenom;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $telephone;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $pseudo_facebook;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Commande::class)]
    private $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = mb_strtoupper($nom);

        return $this;
    }

    public function getPrenom(): ?string
    {
        return mb_strtoupper($this->prenom);
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = mb_strtoupper($prenom);

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPseudoFacebook(): ?string
    {
        return $this->pseudo_facebook;
    }

    public function setPseudoFacebook(?string $pseudo_facebook): self
    {
        $this->pseudo_facebook = $pseudo_facebook;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes[] = $commande;
            $commande->setClient($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getClient() === $this) {
                $commande->setClient(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return mb_strtoupper($this->prenom . ' ' . $this->nom);
    }
}
