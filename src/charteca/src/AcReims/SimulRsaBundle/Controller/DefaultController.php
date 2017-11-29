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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
	public function indexAction()
	{
			// TODO créer la page pour charger ceci
			//$tab = $this->get('simul_rsa.attributs')->getAttributsRsaLdap("fbocahu");
			//dump ($tab);
			//if (count($tab)) $this->get('session')->set("_simulRsa_values", $tab);
			//else $this->get('session')->remove("_simulRsa_values");

		// On ne retourne rien pour l'instant
		return ([]);
	}
}
