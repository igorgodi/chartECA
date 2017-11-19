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
use AppBundle\Entity\NoPersist\Charte;
use AppBundle\Entity\NoPersist\ValidDemandeUtilisationEcaRefus;

use AppBundle\Form\CharteType;
use AppBundle\Form\ValidDemandeUtilisationEcaRefusType;

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
			// Récupérer la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser();
			// Passer cet utilisateur en attente d'activation
			$this->get('app.gestion.utilisateur')->etatCompteAttenteActivation($user);
			// Envoi de la notification par mail aux modérateurs et journalise
			$this->get('app.notification.mail')->demandeOuvertureCompteEca($user);
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
			// Récupérer la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser();
			// Passer cet utilisateur en inactif et journalise
			$this->get('app.gestion.utilisateur')->etatCompteInactif($user); 
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
	 * Fonctionnalité ???? : Revalider la charte (sans modération ultérieure)
	 *
	 * @Route("/revalidation_charte", name="revalidation_charte")
	 * @Template()
	 * @Security("has_role('ROLE_USER_REVALIDATION_CHARTE')")
	 */
	public function revaliderCharteAction(Request $request)
	{
		// Si la requête est en POST et que l'on clique sur le bouton accepter
		if ($request->isMethod('POST') && $request->request->get("submit")=="accepter") 
		{
			// Récupérer la fiche utilisateur
			$user=$this->get('app.service_rsa')->getUser();
			// Ecriture du flag 'ECA|UTILISATEUR||' dans l'annuaire, au cas où la revalidation se fasse après le delai de blocage du compte
			$this->get('app.writer_ldap')->ajoutEntreeAttributApplicationLocale($user->getUsername(), "ECA", "UTILISATEUR", "", "");
			// Passer cet utilisateur en attente d'activation
			$this->get('app.gestion.utilisateur')->etatCompteActif($user);
			// Journalisation
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Validation de la charte par l'utilisateur");
			// Affichage dans l'interface web
			$request->getSession()->getFlashBag()->add("notice", "Merci d'avoir accepté la charte");
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
		// Rien à transmettre ici
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
		// TODO : devel phase 2
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
		// TODO : devel phase 2
		$this->get('logger')->notice("TODO à developper fonctionnalité 5");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 6a : Consulter l'état des comptes ECA
	 *
	 * @Route("/consulter_etat", name="consulter_etat")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
	 */
	public function consulterEtatAction(Request $request)
	{
		// Tableau de liste des utilisateurs ChartECA
		return ([
			'users' => $this->get('doctrine')->getManager()->getRepository('AppBundle:User')->findBy([], ['username' => 'ASC'])
			]);
	}

	/**
	 * Fonctionnalité 6b : Consulter l'état d'un compte
	 *
	 * @Route("/consulter_etat/{id}", requirements={"id" = "\d+"}, name="consulter_etat_user")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
	 *
	 * NOTE : ici on fait de l'auto conversion l'entrée de la table User correspondant à l'id '$id' est chargée
	 *	ceci est la magie de DoctrineParamConverter : https://openclassrooms.com/courses/developpez-votre-site-web-avec-le-framework-symfony/convertir-les-parametres-de-requetes
	 *	Si l'utilisateur n'est pas trouvé, ceci génère une erreur 404
	 *
	 *	Personnaliser son paramConverter : 
	 *		- https://zestedesavoir.com/tutoriels/620/developpez-votre-site-web-avec-le-framework-symfony2/397_astuces-et-points-particuliers/2008_utiliser-des-paramconverters-pour-convertir-les-parametres-de-requetes/
	 *		- https://stfalcon.com/en/blog/post/symfony2-custom-paramconverter
	 */
	public function consulterEtatUserAction(Request $request, User $user)
	{
		// Affiche un utilisateur et son journal
		return ([	'user' => $user, 
				'logs' => $this->get('doctrine')->getManager()->getRepository('AppBundle:Log')->findBy(['username'=> $user->getUsername()], ['date' => 'DESC'] )
			]);
	}

	/**
	 * Fonctionnalité 7a : Lister les demandes d'utilisation ECA  en attente de modération
	 *
	 * @Route("/moderer_demandes_utilisation", name="moderer_demandes_utilisation_liste")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 */
	public function modererDemandesListeAction(Request $request)
	{
		// Tableau de liste des demandes en attente de modération
		return ([
			'users' => $this->get('doctrine')->getManager()->getRepository('AppBundle:User')->findBy(['etatCompte'=> User::ETAT_COMPTE_ATTENTE_ACTIVATION], ['username' => 'ASC'] )
			]);
	}

	/**
	 * Fonctionnalité 7b : Modérer les demandes d'utilisation ECA 
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
	// TODO : pour les fonctionnalités 7b et 7c, fabriquer un paramConverter perso redirigeant vers moderer_demandes_utilisation_liste avec un flashbag error si user non trouvé ou pas en cours d'activation
	public function modererDemandesAction(Request $request, User $user)
	{
		// On vérifie que l'utilisateur est bien en attente :
		if ($user->getEtatCompte() != User::ETAT_COMPTE_ATTENTE_ACTIVATION)
		{ 
			$request->getSession()->getFlashBag()->add("error", "L'utilisateur username='" . $user->getUsername() . "' (id='" . $user->getid() . "') n'est pas en attente d'activation");
			return $this->redirectToRoute('moderer_demandes_utilisation_liste', []);
		}
		// Si la requête est en POST et que l'on clique sur le bouton accepter
		if ($request->isMethod('POST') && $request->request->get("submit")=="accepter") 
		{
			// Ecriture du flag 'ECA|UTILISATEUR||' dans l'annuaire, si la mise à jour du flag à ratéé
			$this->get('app.writer_ldap')->ajoutEntreeAttributApplicationLocale($user->getUsername(), "ECA", "UTILISATEUR", "", "");
			// Passer cet utilisateur en actif
			$this->get('app.gestion.utilisateur')->etatCompteActif($user); 
			// Envoi de la notification par mail à l'utilisateur et journalise
			$this->get('app.notification.mail')->demandeOuvertureCompteEcaAcceptee($user);
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Le modérateur '" . $this->get('app.service_rsa')->getUser()->getCn() . "' à accepté la demande d'activation de compte ECA");
			// Affichage dans l'interface web
			$request->getSession()->getFlashBag()->add("notice", "La demande d'utilisation ECA pour l'utilisateur uid='" . $user->getUsername() . "' a été acceptée");
			// On redirige vers la liste des comptes ECA : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			return $this->redirectToRoute('moderer_demandes_utilisation_liste', []);
		}
		// Si pas de soumission ou invalide, on affiche le formulaire de demande et le journal
		return ([	'user' => $user, 
				'logs' => $this->get('doctrine')->getManager()->getRepository('AppBundle:Log')->findBy(['username'=> $user->getUsername()], ['date' => 'DESC'] )
			]);
	}

	/**
	 * Fonctionnalité 7c : Modérer les demandes d'utilisation ECA : cas du refus
	 *
	 * @Route("/moderer_demandes_utilisation/{id}/refus", requirements={"id" = "\d+"}, name="moderer_demandes_utilisation_refus")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ADMIN')")
	 *
	 * NOTE : idem méthode modererDemandesListeAction()
	 */
	// TODO : pour les fonctionnalités 7b et 7c, fabriquer un paramConverter perso redirigeant vers moderer_demandes_utilisation_liste avec un flashbag error si user non trouvé ou pas en cours d'activation
	public function modererDemandesRefusAction(Request $request, User $user)
	{
		// On vérifie que l'utilisateur est bien en attente :
		if ($user->getEtatCompte() != User::ETAT_COMPTE_ATTENTE_ACTIVATION)
		{ 
			$request->getSession()->getFlashBag()->add("error", "L'utilisateur username='" . $user->getUsername() . "' (id='" . $user->getid() . "') n'est pas en attente d'activation");
			return $this->redirectToRoute('moderer_demandes_utilisation_liste', []);
		}
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
			// Passer cet utilisateur en attente inactif
			$this->get('app.gestion.utilisateur')->etatCompteInactif($user); 
			// Envoi de la notification par mail à l'utilisateur et journalise
			$this->get('app.notification.mail')->demandeOuvertureCompteEcaRefusee($user, $validDemandeUtilisationEcaRefus->getMotifRefus());
			$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Le modérateur '" . $this->get('app.service_rsa')->getUser()->getCn() . "' à refusé la demande d'activation de compte ECA. Motif : '" . $validDemandeUtilisationEcaRefus->getMotifRefus() . "'");
			// Message à afficher
			$request->getSession()->getFlashBag()->add('notice', "La demande d'utilisation a été refusée pour l'utilisateur " . $user->getUsername());
			// On redirige vers la liste des comptes ECA : redirection HTTP : donc pas besoin de recharger le profil Utilisateur
			return $this->redirectToRoute('moderer_demandes_utilisation_liste', []);
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
		// TODO : devel phase 2
		$this->get('logger')->notice("TODO à developper fonctionnalité 8");

		// replace this example code with whatever you need
		return ([]);
	}

	/**
	 * Fonctionnalité 9 : Consulter le journal des motifs des demandes de desactivation des comptes ECA
	 *
	 * @Route("/consulter_demandes_desactivation", name="consulter_demandes_desactivation")
	 * @Template()
	 * @Security("has_role('ROLE_MODERATEUR') or has_role('ROLE_ASSISTANCE') or has_role('ROLE_ADMIN')")
	 */
	public function consulterDemandesDesactivationAction(Request $request)
	{
		// TODO : devel phase 2
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
		// Création du formulaire
		$charte = new Charte();
		$form   = $this->get('form.factory')->create(CharteType::class, $charte);
		// Récupérer la requête dans le formulaire pour assurer la récupération des données renvoyées
		$form->handleRequest($request);
		// Si le formulaire est soumis ET valide
		if ($form->isSubmitted() &&  $form->isValid()) 
		{
			// Récupérer les données du formulaire
			$charte = $form->getData();
			// L'annotation ci-dessous permet de forcer le type de $file
			/** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
			$file = $charte->getFile();
			// Déplacer le fichier dans le répertoire charte à la racine en le nommant charte.pdf
			$file->move("charte", "charte.pdf");
			// TODO : Vider le spooler de taches (via service) pour la tache "publicationCharte"

			// Lister les utilisateurs actifs afin de forcer une ravlaidation
			$users = $this->get('doctrine')->getRepository('AppBundle:User')->findByEtatCompte(User::ETAT_COMPTE_ACTIF);
			foreach ($users as $user)
			{
				// Revalidation
				$this->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user);
				// Journaliser
				$this->get('app.journal_actions')->enregistrer($user->getUsername(), "Publication d'une nouvelle charte, revalidation obligatoire");
				// Envoyer une notification de revalidation de charte
				$this->get('app.notification.mail')->revalidationCharte($user);
				// TODO : Mettre en spooler ($user;"publicationCharte") + journal inscription du spooler

			}
			// Message à afficher
			$request->getSession()->getFlashBag()->add('notice', "La nouvelle charte a été éditée. " . count($users) . " utilisateur(s) au statut actif a (ont) reçu une notification de revalidation de la charte");
			// TODO : $request->getSession()->getFlashBag()->add('notice', "La nouvelle charte a été éditée. " . count($users) . " utilisateur(s) au statut actif  passeront en revalidation et vont recevoir une notification de revalidation de la charte durant la nuit");
			// On redirige vers la page d'accueil
			return $this->redirectToRoute('homepage', []);
		}

		// replace this example code with whatever you need
		return ([ 'form' => $form->createView() ]);
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
		// TODO : devel phase 2
		$this->get('logger')->notice("TODO à developper fonctionnalité 11");

		// replace this example code with whatever you need
		return ([]);
	}

}
