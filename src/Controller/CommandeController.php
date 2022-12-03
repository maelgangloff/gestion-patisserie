<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Konekt\PdfInvoice\InvoicePrinter;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Transliterator;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function index(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll()
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function new(Request $request, CommandeRepository $commandeRepository): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandeRepository->add($commande->setDocToken(Uuid::uuid4()));
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function edit(Request $request, Commande $commande, CommandeRepository $commandeRepository): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandeRepository->add($commande);
            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(Request $request, Commande $commande, CommandeRepository $commandeRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commande->getId(), $request->request->get('_token'))) {
            $commandeRepository->remove($commande);
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/livree', name: 'app_commande_livree', methods: ['POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function livree(Request $request, Commande $commande, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('livree' . $commande->getId(), $request->request->get('_token'))) {
            $commande->setModePaiement($request->request->get('mode_paiement'));
            if ($commande->getModePaiement() != 'ACC' && $commande->getDateLivraison() == null) $commande->setDateLivraison(new DateTime('now', new DateTimeZone('Europe/Paris')));
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/prete', name: 'app_commande_prete', methods: ['POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function prete(Request $request, Commande $commande, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('prete' . $commande->getId(), $request->request->get('_token'))) {
            $commande->setPrete(true);
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/facture', name: 'app_commande_facture', methods: ['GET'])]
    public function generateInvoice(Commande $commande, Request $request): Response
    {
        $token = $commande->getDocToken();
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($token == '' || $commande->getDateLivraison() == null || $request->query->get('ticket') != $token) return new Response("Accès refusé: adresse invalide");
        }
        $reference = self::makeInvoiceReference($this->getParameter('societe_acronyme'), $commande);
        $invoice = self::makeInvoice([$this->getParameter('societe'), ...explode('\n', $this->getParameter('address'))], $this->getParameter('siret'), $reference, $commande);
        if ($docToken = $commande->getDocToken()) $invoice->addParagraph('Retrouvez cette facture en ligne: https:' . $this->generateUrl(
                'app_commande_facture', ['id' => $commande->getId(), 'ticket' => $docToken], UrlGeneratorInterface::NETWORK_PATH
            ));
        $response = new Response($invoice->render('FACTURE_' . $reference . '.pdf', 'S'));
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'FACTURE_' . $reference . '.pdf'));
        return $response;
    }

    #[Route('/{id}/devis', name: 'app_commande_devis', methods: ['GET'])]
    public function generateDevis(Commande $commande, Request $request): Response
    {
        $token = $commande->getDocToken();
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($token == '' || $request->query->get('ticket') != $token) return new Response("Accès refusé: adresse invalide");
        }
        $reference = self::makeInvoiceReference($this->getParameter('societe_acronyme'), $commande);
        $invoice = self::makeInvoice([$this->getParameter('societe'), ...explode('\n', $this->getParameter('address'))], $this->getParameter('siret'), $reference, $commande);
        $invoice->setType('Devis');
        if ($docToken = $commande->getDocToken()) $invoice->addParagraph('Retrouvez ce devis en ligne: https:' . $this->generateUrl(
                'app_commande_devis', ['id' => $commande->getId(), 'ticket' => $docToken], UrlGeneratorInterface::NETWORK_PATH
            ));
        $response = new Response($invoice->render('DEVIS_' . $reference . '.pdf', 'S'));
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'DEVIS_' . $reference . '.pdf'));
        return $response;
    }

    public static function makeInvoiceReference(string $societe_acronyme, Commande $commande): string
    {
        return $societe_acronyme . $commande->getDateLivraisonSouhaitee()->format('Ym') . sprintf("%05d", $commande->getId());
    }

    public static function makeInvoice(array $fromAddress, string $siret, string $reference, Commande $commande): InvoicePrinter
    {
        $invoice = new InvoicePrinter('A4', '€', 'fr');
        $invoice->setFontSizeProductDescription(10);
        $invoice->setNumberFormat('.', ' ', 'right', true, false);
        $invoice->changeLanguageTerm('price', 'Prix');
        $invoice->changeLanguageTerm('product', 'Désignation');
        $invoice->setLogo('logo.png');
        $invoice->setColor("#00000");
        $invoice->setType("Facture");
        $invoice->setReference($reference);
        $invoice->setDate(($commande->getDateLivraison() ?? $commande->getDateLivraisonSouhaitee())->format('d/m/Y'));
        $invoice->setFrom($fromAddress);
        $invoice->setTo([$commande->getClient()->__toString()]);
        $invoice->addItem("Commande", $commande->getCommande(), false, false, $commande->getMontant(), false, $commande->getMontant());
        $modePaiement = $commande->getModePaiement();
        $invoice->addTotal("Total TTC", $commande->getMontant(), true);
        $invoice->addParagraph('TVA non applicable, article 293B du code général des impôts.');
        if ($modePaiement !== 'NP' && $modePaiement !== null && $modePaiement !== 'ACC') {
            $invoice->addTitle('Règlement:');
            $invoice->addParagraph('Date de règlement: ' . $commande->getDateLivraison()->format('d/m/Y'));
            $invoice->addParagraph('Mode de règlement: ' . ($modePaiement === 'CB' ? 'Carte bancaire' : ($modePaiement === 'ESP' ? 'Espèces' : ($modePaiement === 'VIR' ? 'Virement' : 'Chèque'))));
            $invoice->addParagraph("");
            $invoice->addParagraph("");
        }
        $invoice->setFooternote(strtoupper(Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')->transliterate($fromAddress[0])) . ' - SIRET ' . $siret);
        return $invoice;
    }
}
