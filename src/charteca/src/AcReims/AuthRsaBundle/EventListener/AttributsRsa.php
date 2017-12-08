<?php
/*
 *   Ecouteur d'évenement de gestion de la récupération des attributs RSA
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
	/** Service activé si true */
	private $actif;

	/** liste des attributs à charger */
	private $attributs;

	/** Oblet logger */
	private $logger;

	/** Pile de requêtes */
	private $requestStack;

	/** Tableau des valeurs d'attributs */
	private $valAttribs;

	/** Booleen permettant de stopper sur une erreur 500 si il manque des attributs */
	private $stop;

	/**
	 * Constructeur
	 *
	 * @param $actif True si le module est activé
	 * @param $attributs Tableau descriptif des attributs à récupérer dans RSA (provient du ficher de configuration de l'application)
	 * @param $logger Objet logger
	 * @param $requestStack Objet de pile de requête
	 */
	public function __construct($actif, $attributs, LoggerInterface $logger, RequestStack $requestStack)
	{
		// Sauvegarde des objets
		$this->attributs = $attributs;
		$this->actif = $actif;
		$this->logger = $logger;
		$this->requestStack = $requestStack;

		// RAZ valeurs
		$this->valAttribs = [];
		$this->stop = false;
	}

	/**
	 * Méthode de chargement des attributs. Appelé par l'évènement kernel.request (voir le fichier services.yml de ce bundle)
	 */
	public function loadAttribs()
	{
		//--> Activé ou pas ???
		if (!$this->actif) return;

		//--> Récupère la requête courante dans le service
		$request = $this->requestStack->getCurrentRequest();

		//--> On ne traite cet évenement que dans le AppBundle : évite de charger les variables RSA si on est ailleurs comme le profiler 
		// 	évite de bloquer les autres bundles si RSA n'est pas actif
		// TODO : réaliser une liste configurable si besoin !!!!!
		//$controller = $request->attributes->get('_controller');
		//if (	   !preg_match("/^AppBundle\\\\/", $controller) 
		//	&& !preg_match("/^AcReims\\\\SimulRsaBundle\\\\/", $controller)) return null;

		//--> Récupération des attributs RSA nécessaires à l'application et traitement des champs obligatoires
		foreach ($this->attributs as $key => $value)
		{
			// On récupère l'attribut multivalué ou pas
			if ($value['multivalue']) $this->valAttribs[$key] = explode(",", $request->headers->get($key, ""));
			else $this->valAttribs[$key] = $request->headers->get($key, "");	
			// On vérifie si l'obligation de trouver cet attribut est respectée
			if ($value['obligatoire'] && $this->valAttribs[$key] == "")
			{
				$this->logger->critical("Attribut RSA '$key' non trouvé");
				$this->stop = true;
			}
		}
		dump ($this->valAttribs);
	}

	/**
	 * Méthode getter des attributs
	 *
	 * @return Retourne un tableau des attributs
	 *
	 * @throw HttpException Erreur 500 si un attribut obligatoire n'a pas été trouvé
	 */
	public function getAttribs()
	{
		//--> Activé ou pas ???
		if (!$this->actif) return[];

		//--> Si le tableau n'est pas encore chargé, le charger
		if (count($this->valAttribs) == 0) $this->loadAttribs();

		//-> Si erreur de lecture des attributs dans la phase précédente, on envoi une exception erreur 500 qui va 
		//	- Interrompre l'action en cours
		//	- générer un log critical et donc un mail d'erreur en production.
		// 		voir http://api.symfony.com/2.7/Symfony/Component/HttpKernel/Exception/HttpException.html
		if ($this->stop) throw new HttpException(500, "Erreur lecture des attributs RSA");

		// Retourne le tableau d'attributs
		return ($this->valAttribs);
	}
}
?>
