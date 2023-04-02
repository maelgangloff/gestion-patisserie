<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted("ROLE_ADMIN")]
class PatisserieController extends AbstractController
{
    #[Route('/', name: 'admin')]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findAll();
        $chiffreAffairePrevisionnelMois = AdministratifController::getChiffreAffaire($commandes, new DateTime('first day of this month 00:00:00'), new DateTime('last day of this month 00:00:00'), true);
        $chiffreAffaireMois = AdministratifController::getChiffreAffaire($commandes, new DateTime('first day of this month 00:00:00'), new DateTime('last day of this month 00:00:00'));
        $chiffreAffaireMoisPrevisionnelProchain = AdministratifController::getChiffreAffaire($commandes, new DateTime('first day of next month 00:00:00'), new DateTime('last day of next month 00:00:00'), true);
        $chiffreAffaireCumule = AdministratifController::getChiffreAffaire($commandes, new DateTime('first day of January 00:00:00'), new DateTime('last day of December 00:00:00'));
        $chiffreAffairePrevisionnelCumule = AdministratifController::getChiffreAffaire($commandes, new DateTime('first day of January 00:00:00'), new DateTime('last day of December 00:00:00'), true);
        return $this->render('index.html.twig', [
            'commandes' => $commandes,
            'chiffreAffairePrevisionnelMois' => $chiffreAffairePrevisionnelMois,
            'chiffreAffaireMois' => $chiffreAffaireMois,
            'chiffreAffaireMoisPrevisionnelProchain' => $chiffreAffaireMoisPrevisionnelProchain,
            'chiffreAffaireCumule' => $chiffreAffaireCumule,
            'chiffreAffairePrevisionnelCumule' => $chiffreAffairePrevisionnelCumule
        ]);
    }
}
