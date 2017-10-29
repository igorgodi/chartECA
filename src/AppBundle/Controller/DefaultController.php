<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;

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


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 1 : Demande d'utilisation 
	 *
	 * @Route("/demande_utilisation", name="demande_utilisation")
	 * @Template()
	 */
	public function demandeUtilisationAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 2 : Consulter la charte
	 *
	 * @Route("/consulter_charte", name="consulter_charte")
	 * @Template()
	 */
	public function consulterCharteAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 3 : Désactivation le compte ECA
	 *
	 * @Route("/desactiver_compte", name="desactiver_compte")
	 * @Template()
	 */
	public function desactiverCompteAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 4 : Demande d'augmentation de quota
	 *
	 * @Route("/augmentation_quota", name="augmentation_quota")
	 * @Template()
	 */
	public function augmentationQuotaAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 5 : Consulter l'état des comptes 
	 *
	 * @Route("/consulter_etat", name="consulter_etat")
	 * @Template()
	 */
	public function consulterEtatAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 6 : Modérer les demandes d'utilisation ECA 
	 *
	 * @Route("/moderer_demandes", name="moderer_demandes")
	 * @Template()
	 */
	public function modererDemandesAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 7 : Modérer les demandes d'augmentation de quota 
	 *
	 * @Route("/moderer_demandes_quota", name="moderer_demandes_quota")
	 * @Template()
	 */
	public function modererDemandesQuotaAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 8 : Consulter les demandes de desactivation 
	 *
	 * @Route("/consulter_demandes_desactivation", name="consulter_demandes_desactivation")
	 * @Template()
	 */
	public function consulterDemandesDesactivationAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

	/**
	 * Fonctionnalité 9 : Publier la charte d'utilisation 
	 *
	 * @Route("/publier_charte", name="publier_charte")
	 * @Template()
	 */
	public function publierCharteAction(Request $request)
	{


		// replace this example code with whatever you need
		return ([
			'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
		]);
	}

}
