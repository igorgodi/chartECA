<?php
/*
 *   Controleur chargé de gérer les actions de l'application
 *
 *   Copyright 2017        igor.godi@ac-reims.fr
 *	 DSI4 - Pôle-projets - Rectorat de l'académie de Reims.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/")
 * NOTE : il est possible d'implémenter une annotation sécurity sur le contrôleur complet : (enlever les // devant @ pour activer)
 * //@Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
 */
class DefaultController extends Controller
{
	/**
	 * Page d'accueil de l'application
	 *
	 * @Route("/", name="homepage")
	 * @Template()
	 */
	public function indexAction(Request $request)
	{
		// On ne retourne rien ici
		return ([]);
	}

	/**
	 * Fonctionnalité 1 : Demande d'utilisation de ECA
	 *
	 * @Route("/demande_utilisation", name="demande_utilisation")
	 * @Template()
	 * @Security("has_role('ROLE_USER_INACTIF')")
	 */
	public function demandeUtilisationAction(Request $request)
	{
		// Si la requête est en POST et que l'on clique sur le bouton accepter
		if ($request->isMethod('POST') && $request->request->get("submit")=="accepter") 
		{
			// Récupérer et mettre à jour la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser()->setEtatCompte(User::ETAT_COMPTE_ATTENTE_ACTIVATION);
			// Enregistrer la fiche utilisateur
			$em = $this->get('doctrine')->getManager(); 
			$em->persist($user);
			$em->flush();
			// Inscrire dans le journal
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "demande_utilisation", "Demande d'utilisation ECA par l'utilisateur");
			// Affichage modification
			$request->getSession()->getFlashBag()->add("notice", "Votre demande est en attente de modération");

			// TODO : réaliser un service (ou 2 imbriqués, un réutilisable et l'autre pas.... et envoie HTML ????


// Envoyer le mail à tout les modérateurs
$listeModerateurs = array();
$moderateurs = $em->getRepository('AppBundle:Moderateur')->findAll();
foreach($moderateurs as $moderateur) $listeModerateurs[] = $moderateur->getEmail();
// TODO : traiter le pb de pas de modérateurs dans ce cas, message dans journal user et error dans log appli.
if (count($listeModerateurs) == 0) 
{
	$this->get('app.journal_actions')->enregistrer($user->getUsername(), "demande_utilisation", "Pas d'émail de notification envoyé aux modérateurs car aucun de définit !!!");
	$this->get('logger')->error("Pas de modérateur définit, envoi d'email impossible");
}
else
{
	// Create the message
	$mail = (new \Swift_Message())
	  // Give the message a subject
	  ->setSubject("Votre demande d'accès à ECA est en attente de modération")
	  // Set the From address with an associative array
	  ->setFrom($this->container->getParameter('notification_from'))
	  // Set the To addresses with an associative array (setTo/setCc/setBcc)
	  ->setTo($listeModerateurs)
	  // Give it a body
	  ->setBody("Vous avez demandé un accès à la plateforme ECA, votre demande à été transmise par mail aux modérateurs qui s'éfforcerons d'y répondre dans les plus brefs délais.\nCordialement.")
	  // And optionally an alternative body
	  //->addPart('<q>Here is the message itself</q>', 'text/html')
	  // Optionally add any attachments
	 // ->attach(Swift_Attachment::fromPath('my-document.pdf'))
	  ;
	$this->get('mailer')->send($mail);
	$this->get('app.journal_actions')->enregistrer($user->getUsername(), "demande_utilisation", "email envoyé aux modérateurs (" . implode (" ; ", $listeModerateurs) . ")");
}



			// On redirige vers la page d'accueil : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			return $this->redirectToRoute('homepage', []);
		}
		// Si pas de soumission, on affiche le formulaire de demande
		return ([]);
	}

	/**
	 * Fonctionnalité 2 : Etat demande d'utilisation en cours
	 *
	 * @Route("/etat_demande_utilisation", name="etat_demande_utilisation")
	 * @Template()
	 * @Security("has_role('ROLE_USER_ATTENTE_ACTIVATION')")
	 */
	public function etatDemandeUtilisationAction(Request $request)
	{
		// Si la requête est en POST et que l'on clique sur le bouton annuler
		if ($request->isMethod('POST') && $request->request->get("submit")=="annuler") 
		{
			// Récupérer et mettre à jour la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser()->setEtatCompte(User::ETAT_COMPTE_INACTIF);
			// Enregistrer la fiche utilisateur
			$em = $this->get('doctrine')->getManager(); 
			$em->persist($user);
			$em->flush();
			// Inscrire dans le journal
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "annulation_demande_utilisation", "Annulation demande d'utilisation ECA par l'utilisateur");
			// Affichage modification
			$request->getSession()->getFlashBag()->add("notice", "Votre demande d'utilisation ECA a été annulée");
			// On redirige vers la page d'accueil : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			return $this->redirectToRoute('homepage', []);
		}
		// Si pas de soumission, on affiche le formulaire de demande
		return ([]);
	}

	/**
	 * Fonctionnalité 3 : Consulter la charte
	 *
	 * @Route("/consulter_charte", name="consulter_charte")
	 * @Template()
	 */
	public function consulterCharteAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 3");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 4 : Désactiver le compte ECA
	 *
	 * @Route("/desactiver_compte", name="desactiver_compte")
	 * @Template()
	 * @Security("has_role('ROLE_USER_ACTIF')")
	 */
	public function desactiverCompteAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 4");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 5 : Demande d'augmentation de quota
	 *
	 * @Route("/augmentation_quota", name="augmentation_quota")
	 * @Template()
	 * @Security("has_role('ROLE_USER_ACTIF')")
	 */
	public function augmentationQuotaAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 5");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 6 : Consulter l'état des comptes ECA
	 *
	 * @Route("/consulter_etat", name="consulter_etat")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
	 */
	public function consulterEtatAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 6");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 7 : Modérer les demandes d'utilisation ECA 
	 *
	 * @Route("/moderer_demandes", name="moderer_demandes")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 */
	public function modererDemandesAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 7");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 8 : Modérer les demandes d'augmentation de quota 
	 *
	 * @Route("/moderer_demandes_quota", name="moderer_demandes_quota")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 */
	public function modererDemandesQuotaAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 8");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 9 : Consulter les demandes de desactivation 
	 *
	 * @Route("/consulter_demandes_desactivation", name="consulter_demandes_desactivation")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
	 */
	public function consulterDemandesDesactivationAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 9");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 10 : Publier la charte d'utilisation 
	 *
	 * @Route("/publier_charte", name="publier_charte")
	 * @Template()
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function publierCharteAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 10");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 11 : Activer/Désactiver l'augmentation de quota 
	 *
	 * @Route("/gestion_activation_quotas", name="gestion_activation_quotas")
	 * @Template()
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function gestionQuotasAction(Request $request)
	{
		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 11");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 12 : Gestion des annexes de la charte
	 *
	 * @Route("/gestion_annexes_charte", name="gestion_annexes_charte")
	 * @Template()
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function gestionAnnexesCharteAction(Request $request)
	{
		// TODO : Fonctinnalité à réaliser ou pas ?????

		// TODO : devel
		$this->get('logger')->notice("TODO à developper fonctionnalité 12");

		// replace this example code with whatever you need
		return ([]);
	}

}
