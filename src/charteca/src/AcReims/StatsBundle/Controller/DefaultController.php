<?php
/*
 *   Controleur chargé de gérer les pages de lancement des scripts de recette en ligne de commande
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

namespace AcReims\StatsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Définition de la route principale du contrôleur :
 * @Route("/_statistiques")
 */
class DefaultController extends Controller
{
	/**
	 * Page d'execution de la commande app:recette:cleanbase
	 *
	 * @Route("/", name="_statistiques_index")
	 * @Template()
	 */
	public function indexAction()
	{
		// On ne retourne rien
		return ([]);	
	}

}
