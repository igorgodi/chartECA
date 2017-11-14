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
use AppBundle\Entity\NoPersist\ValidDemandeUtilisationEcaRefus;

use AppBundle\Form\ValidDemandeUtilisationEcaRefusType;

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


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
			// Envoi de la notification par mail aux modérateurs
			$this->get('app.notification.mail')->demandeOuvertureCompteEca($user);
			// Inscrire dans le journal
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Demande d'utilisation ECA par l'utilisateur");
			// Affichage dans l'interface web
			$request->getSession()->getFlashBag()->add("notice", "Votre demande est en attente de modération");
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
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Annulation demande d'utilisation ECA par l'utilisateur");
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
	 * Fonctionnalité 7a : Modérer les demandes d'utilisation ECA 
	 *
	 * @Route("/moderer_demandes_utilisation/{id}", requirements={"id" = "\d+"}, name="moderer_demandes_utilisation")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 *
	 * NOTE : ici on fait de l'auto conversion l'entrée de la table User correspondant à l'id '$id' est chargée
	 *	ceci est la magie de DoctrineParamConverter : https://openclassrooms.com/courses/developpez-votre-site-web-avec-le-framework-symfony/convertir-les-parametres-de-requetes
	 *	Si l'utilisateur n'est pas trouvé, ceci génère une erreur 404
	 *
	 *	Personnaliser son paramConverter : 
	 *		- https://zestedesavoir.com/tutoriels/620/developpez-votre-site-web-avec-le-framework-symfony2/397_astuces-et-points-particuliers/2008_utiliser-des-paramconverters-pour-convertir-les-parametres-de-requetes/
	 *		- https://stfalcon.com/en/blog/post/symfony2-custom-paramconverter
	 */
	// TODO : réaliser un param converter qui récupère l'objet User sur l'id ET aussi $user->getEtatCompte() == User::ETAT_COMPTE_ATTENTE_ACTIVATION sinon erreur 404
	public function modererDemandesAction(Request $request, User $user)
	{
		// On vérifie que l'utilisateur est bien en attente :
		// TODO : renvoyer à la page consulter_etat avec flshbag error pour message
		if ($user->getEtatCompte() != User::ETAT_COMPTE_ATTENTE_ACTIVATION) throw new NotFoundHttpException("L'utilisateur username='" . $user->getUsername() . "' (id='" . $user->getid() . "') n'est pas en attente d'activation");
		// Si la requête est en POST et que l'on clique sur le bouton accepter
		if ($request->isMethod('POST') && $request->request->get("submit")=="accepter") 
		{
			// Récupérer et mettre à jour la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser();
			// Enregistrer la fiche utilisateur
			// TODO : devel
			//$user->setEtatCompte(User::ETAT_COMPTE_ACTIF);
			//$em = $this->get('doctrine')->getManager(); 
			//$em->persist($user);
			//$em->flush();
			// TODO : Envoi de la notification par mail aux modérateurs
			//$this->get('app.notification.mail')->demandeOuvertureCompteEca($user);
			// Inscrire dans le journal TODO : nom modo
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Le modérateur (TODO xxxxx) à accepté ");
			// TODO : basculer le flag ECA avec le LdapWriter
			// Affichage dans l'interface web
			$request->getSession()->getFlashBag()->add("notice", "La demande d'utilisation ECA pour l'utilisateur uid='" . $user->getUsername() . "' a été acceptée");
			// On redirige vers la liste des comptes ECA : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			return $this->redirectToRoute('consulter_etat', []);
		}
		// Si pas de soumission ou invalide, on affiche le formulaire de demande et le journal
		return ([	'user' => $user, 
				'logs' => $this->get('doctrine')->getManager()->getRepository('AppBundle:Log')->findBy(['username'=> $user->getUsername()], ['date' => 'DESC'] )
			]);
	}

	/**
	 * Fonctionnalité 7b : Modérer les demandes d'utilisation ECA : cas du refus
	 *
	 * @Route("/moderer_demandes_utilisation_refus/{id}", requirements={"id" = "\d+"}, name="moderer_demandes_utilisation_refus")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 *
	 * NOTE : idem méthode modererDemandesAction()
	 */
	// TODO : réaliser un param converter qui récupère l'objet User sur l'id ET aussi $user->getEtatCompte() == User::ETAT_COMPTE_ATTENTE_ACTIVATION sinon erreur 404
	public function modererDemandesRefusAction(Request $request, User $user)
	{
		// On vérifie que l'utilisateur est bien en attente :
		// TODO : renvoyer à la page consulter_etat avec flshbag error pour message
		if ($user->getEtatCompte() != User::ETAT_COMPTE_ATTENTE_ACTIVATION) throw new NotFoundHttpException("L'utilisateur username='" . $user->getUsername() . "' (id='" . $user->getid() . "') n'est pas en attente d'activation");
		// Créer un objet porteur du formulaire
		$validDemandeUtilisationEcaRefus = new ValidDemandeUtilisationEcaRefus();
    		$form = $this->get('form.factory')->create(ValidDemandeUtilisationEcaRefusType::class, $validDemandeUtilisationEcaRefus);
		// Récupérer la requête dans le formulaire pour assurer la récupération des données renvoyées
		$form->handleRequest($request);
		// Si le formulaire est soumis ET valide
		if ($form->isSubmitted() &&  $form->isValid()) 
		{
			// Récupérer les données du formulaire
			$validDemandeUtilisationEcaRefus = $form->getData();
			// TODO : journaliser, modif flag user, modif flag ldap et notifications
			// Message à afficher
			$request->getSession()->getFlashBag()->add('notice', "La modération a été refusée pour l'utilisateur " . $user->getUsername());
			// On redirige vers la liste des comptes ECA : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			//return $this->redirectToRoute('consulter_etat', []);
		}
		// Si pas de soumission ou invalide, on affiche le formulaire de demande
		return ([	'user' => $user, 
				'form' => $form->createView()]);
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
