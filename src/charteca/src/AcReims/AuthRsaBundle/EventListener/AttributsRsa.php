<?php
/*
 *   Ecouteur d'évenement de gestion de la récupération des attributs RSA pour gérer les utilisateurs de l'application
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

namespace AcReims\AuthRsaBundle\EventListener;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * Classe de gestion de la collecte des attributs RSA et de stopper l'appli si des attributs ne sont pas trouvés
 */
class AttributsRsa
{
	/** Oblet logger */
	private $logger;

	/** Pile de requêtes */
	private $requestStack;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $requestStack Objet de pile de requête
	 */
	public function __construct(LoggerInterface $logger, RequestStack $requestStack)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->requestStack = $requestStack;
	}

	/**
	 * Méthode de chargement des attributs. Appelé par l'évènement kernel.request (voir le fichier services.yml de ce bundle)
	 */
	public function loadAttribs()
	{
		//--> Récupère la requête courante dans le service
		$request = $this->requestStack->getCurrentRequest();

		//--> On ne traite cet évenement que dans le AppBundle : évite de charger les variables RSA si on est ailleurs comme le profiler 
		// 	évite de bloquer les autres bundles si RSA n'est pas actif
		// TODO : réaliser une liste configurable !!!!!
		//$controller = $request->attributes->get('_controller');
		//if (	   !preg_match("/^AppBundle\\\\/", $controller) 
		//	&& !preg_match("/^AcReims\\\\SimulRsaBundle\\\\/", $controller)) return null;

		//--> Récupération des attributs RSA nécessaires à l'application
		$username = $request->headers->get("ct-remote-user", "");
		$email = $request->headers->get("ctemail", "");
		$cn = $request->headers->get("cn", "");
		$attributsApplicationLocale = $request->headers->get("attributapplicationlocale", "");
		$frEduRne = $request->headers->get("fredurne", "");

		//--> Tester les attributs RSA obligatoires (ct-remote-user, ctemail,....) :
		if ($username == "" || $email == "" || $cn == "" ) 
		{
			// On loggue
			if ($username == "") $this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
			if ($email == "") $this->logger->critical("Attribut RSA 'ctemail' non trouvé");
			if ($cn == "") $this->logger->critical("Attribut RSA 'cn' non trouvé");
			// On sort à la moindre erreur
			return;
		}
		dump($username);
	}

	/**
	 * Méthode getter des attributs
	 *
	 * @return Retourne un tableau d'attributs
	 */
	public function getAttribs()
	{
	}
}
?>
