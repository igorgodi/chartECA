<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
		// TODO devel : Test logger et ldapReader
		/*$record = $this->get('app.reader_ldap')->getUser($this->get('app.service_rsa')->getUser()->getUsername());
		if ($record==null)  $this->get('logger')->critical('Fiche ldap non trouvée !!!!');
		else
		{
			$tab = $record->getAttribute("AttributApplicationLocale");
			for ($x=0 ; $x<count($tab) ; $x++) $this->get('logger')->info('Accès accueil. AttributApplicationLocale=' . $tab[$x]);
		}*/
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



		// replace this example code with whatever you need
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


		// replace this example code with whatever you need
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


		// replace this example code with whatever you need
		return ([]);
	}

}
