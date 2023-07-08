<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Commande;
use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

#[IsGranted("ROLE_ADMIN")]
#[Route('/administratif')]
class AdministratifController extends AbstractController
{

    public static function getChiffreAffaire(array $commandes, DateTime $startDate, DateTime $endDate, bool $previsionnel = false): float
    {
        return array_reduce(array_filter($commandes, function (Commande $commande) use ($startDate, $endDate) {
            $dateCommande = $commande->getDateLivraison() ?? $commande->getDateLivraisonSouhaitee();
            return $startDate < $dateCommande && $dateCommande < $endDate;
        }), function ($carry, Commande $commande) use ($previsionnel) {
            $modePaiement = $commande->getModePaiement();
            return $carry + ($previsionnel ? $commande->getMontant() : ($modePaiement !== 'NP' && $modePaiement !== 'ACC' && $modePaiement !== null ? $commande->getMontant() : 0));
        }, 0);
    }

    #[Route('/', name: 'administratif')]
    public function statistiques(CommandeRepository $commandeRepository): Response
    {
        return $this->render('administratif.twig',
            [
                'commandes' => $commandeRepository->findBy([], ['date_livraison' => 'desc'])
            ]);
    }

    #[Route('/factures', name: 'download_invoices')]
    public function downloadInvoices(CommandeRepository $commandeRepository): Response
    {
        $zip = new ZipArchive();
        $zipName = $this->getParameter('societe_acronyme') . '_FACTURES_' . (new DateTime())->format('YmdHms') . '.zip';
        $zip->open($zipName, ZipArchive::CREATE);
        foreach ($commandeRepository->findAll() as $commande) {
            $reference = CommandeController::makeInvoiceReference($this->getParameter('societe_acronyme'), $commande);
            $zip->addFromString($reference . '.pdf', CommandeController::makeInvoice([$this->getParameter('societe'), ...explode('\n', $this->getParameter('address'))], $this->getParameter('siret'), $reference, $commande)->render($reference, 'S'));
        }
        $zip->close();
        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($zipName));
        @unlink($zipName);
        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/rapport', name: 'rapport')]
    public function generateRapport(ClientRepository $clientRepository, CommandeRepository $commandeRepository): Response
    {
        $spreadsheet = new Spreadsheet();

        $clientsSheet = $spreadsheet->getActiveSheet()->setTitle("Liste des clients");

        foreach (range('A', 'E') as $columnID) {
            $clientsSheet->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        $clients = $clientRepository->createQueryBuilder('c')->select('c', 'co')->innerJoin('c.commandes', 'co')->getQuery()->getArrayResult();
        $commandes = $commandeRepository->findAll();
        $clientsSheet->getStyle('C:D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $clientsSheet->fromArray([
            ['Nom', 'Prénom', 'ID Messenger', 'Courriel', 'Téléphone', 'Nombre de commandes', 'Panier moyen'],
            ...array_map(function (array $client) {
                $nb_commandes = count($client['commandes']);
                $panier_moyen = array_reduce($client['commandes'], function ($carry, $commande) {
                    return $carry + $commande['montant'];
                }, 0) / ($nb_commandes == 0 ? 1 : $nb_commandes);
                return [$client['nom'], $client['prenom'], $client['pseudo_facebook'], $client['email'], $client['telephone'], $nb_commandes, $panier_moyen];
            }, $clients)
        ]);

        $commandesSheet = $spreadsheet->createSheet()->setTitle('Livre des recettes');
        $commandesSheet->getStyle('E:E')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        foreach (range('A', 'F') as $columnID) {
            $commandesSheet->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        usort($commandes, function (Commande $c1, Commande $c2) {
            return ($c1->getDateLivraison() ?? $c1->getDateLivraisonSouhaitee()) > ($c2->getDateLivraison() ?? $c2->getDateLivraisonSouhaitee());
        });

        $commandesSheet->fromArray([
            ['Référence', 'Date prise commande', 'Date livraison', 'Client', 'Montant', 'Mode de paiement'],
            ...array_map(function (Commande $commande) {
                return [CommandeController::makeInvoiceReference($this->getParameter('societe_acronyme'), $commande), $commande->getDatePriseCommande(), $commande->getDateLivraison() ?? $commande->getDateLivraisonSouhaitee(), $commande->getClient(), $commande->getMontant(), $commande->getModePaiement()];
            }, $commandes)
        ]);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'RAPPORT_' . $this->getParameter('societe_acronyme') . '_' . (new DateTime())->format('YmdHms') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
