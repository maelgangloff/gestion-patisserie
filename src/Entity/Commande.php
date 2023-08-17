<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CommandeRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $date_prise_commande;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $date_livraison_souhaitee;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $date_livraison;

    #[ORM\Column(type: 'boolean')]
    private ?bool $livraison_domicile;

    #[ORM\Column(type: 'float')]
    private ?float $montant;

    #[ORM\Column(type: 'text')]
    private ?string $commande;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $mode_paiement;

    #[ORM\Column(type: 'boolean')]
    private ?bool $prete;

    #[ORM\ManyToOne(targetEntity: Client::class, fetch: 'EAGER', inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client;

    #[ORM\Column(length: 50)]
    private ?string $doc_token = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatePriseCommande(): ?DateTimeInterface
    {
        return $this->date_prise_commande;
    }

    public function setDatePriseCommande(DateTimeInterface $date_prise_commande): self
    {
        $this->date_prise_commande = $date_prise_commande;

        return $this;
    }

    public function getDateLivraisonSouhaitee(): ?DateTimeInterface
    {
        return $this->date_livraison_souhaitee;
    }

    public function setDateLivraisonSouhaitee(DateTimeInterface $date_livraison_souhaitee): self
    {
        $this->date_livraison_souhaitee = $date_livraison_souhaitee;

        return $this;
    }

    public function getDateLivraison(): ?DateTimeInterface
    {
        return $this->date_livraison;
    }

    public function setDateLivraison(?DateTimeInterface $date_livraison): self
    {
        $this->date_livraison = $date_livraison;

        return $this;
    }

    public function getLivraisonDomicile(): ?bool
    {
        return $this->livraison_domicile;
    }

    public function setLivraisonDomicile(bool $livraison_domicile): self
    {
        $this->livraison_domicile = $livraison_domicile;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getCommande(): ?string
    {
        return $this->commande;
    }

    public function setCommande(string $commande): self
    {
        $this->commande = $commande;

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(?string $mode_paiement): self
    {
        $this->mode_paiement = $mode_paiement === 'NP' ? null : $mode_paiement;

        return $this;
    }

    public function getPrete(): ?bool
    {
        return $this->prete;
    }

    public function setPrete(bool $prete): self
    {
        $this->prete = $prete;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getDocToken(): ?string
    {
        return $this->doc_token;
    }

    public function setDocToken(string $doc_token): self
    {
        $this->doc_token = $doc_token;

        return $this;
    }
}
