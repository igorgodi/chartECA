<?php
/*
 *   Interface du service de gestion de la récupération des attributs RSA
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

namespace AcReims\AuthRsaBundle\Service;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RequestStack;


/**
 * interface de la classe de gestion de la collecte des attributs RSA et de stopper l'appli si des attributs ne sont pas trouvés
 */
interface AttributsRsaInterface
{
	/**
	 * Constructeur
	 *
	 * @param $actif True si le module est activé
	 * @param $attributs Tableau descriptif des attributs à récupérer dans RSA (provient du ficher de configuration de l'application)
	 * @param $logger Objet logger
	 * @param $requestStack Objet de pile de requête
	 */
	public function __construct($actif, $attributs, LoggerInterface $logger, RequestStack $requestStack);

	/**
	 * Méthode de chargement des attributs. Appelé par l'évènement kernel.request (voir le fichier services.yml de ce bundle)
	 */
	public function loadAttribs();

	/**
	 * Méthode getter des attributs
	 *
	 * @return Retourne un tableau des attributs
	 *
	 * @throw HttpException Erreur 500 si un attribut obligatoire n'a pas été trouvé
	 */
	public function getAttribs();
}
?>
