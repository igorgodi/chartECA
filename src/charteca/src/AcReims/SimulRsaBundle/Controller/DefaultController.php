<?php
/*
 *   Controleur chargé de gérer les pages de l'application SimulRSA
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

namespace AcReims\SimulRsaBundle\Controller;

use AcReims\SimulRsaBundle\Entity\NoPersist\ChoixUser;
use AcReims\SimulRsaBundle\Form\ChoixUserType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;


/**
 * Définition de la route principale du contrôleur :
 * @Route("/_simulrsa")
 */
class DefaultController extends Controller
{
	/**
	 * Page d'accueil de simulRsa
	 *
	 * @Route("/", name="_simulrsa_homepage")
	 * @Template()
	 */
	public function indexAction(Request $request)
	{
		// Créer un objet porteur du formulaire
		$choixUser = new ChoixUser();
    		$form = $this->get('form.factory')->create(ChoixUserType::class, $choixUser);
		// Récupérer la requête dans le formulaire pour assurer la récupération des données renvoyées
		$form->handleRequest($request);
		// Si le formulaire est soumis ET valide
		if ($form->isSubmitted() &&  $form->isValid()) 
		{
			// Vider la variable de session
			$this->get('session')->remove("_simulRsa_values");
			// Récupérer les données du formulaire
			$choixUser = $form->getData();
			if ($choixUser->getUid()!="")
			{
				// Récupère la fiche ldap sous forme d'attributs RSA
				$tab = $this->get('simul_rsa.attributs')->getAttributsRsaLdap($choixUser->getUid());
				if (count($tab)) $this->get('session')->set("_simulRsa_values", $tab);
			}
			// On redirige pour réaffichage
			return $this->redirectToRoute('_simulrsa_homepage', []);
		}
		// Si pas de soumission ou invalide, on affiche le formulaire de demande
		return (['form' => $form->createView()]);
	}
}
