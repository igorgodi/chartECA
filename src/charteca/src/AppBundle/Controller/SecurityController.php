<?php
/*
 *   Controleur chargé de gérer les actions liées à la sécurité (Firewall)
 *	Voir app/config/security.yml
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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/")
 */
class SecurityController extends Controller
{
	/**
	 * Destruction de la session Symfony
	 *
	 * @Route("/logout", name="logout")
	 */
	public function logoutAction()
	{
		// Destruction de session
		$this->get('security.context')->setToken(null);
		$this->get('request')->getSession()->invalidate(); 
	}

	/**
	 * Redirection vers la page de déconnection RSA
	 *
	* @Route("/rsa_deconnect", name="rsa_deconnect")
	*/
	public function rsaDecoAction()
	{
		// Redirection vers logout RSA
		return new RedirectResponse($this->container->getParameter('rsaDeco'));

	}

}
