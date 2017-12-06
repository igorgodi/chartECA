<?php
/*
 *   Interface du service de gestion des statistiques utilisateurs
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

namespace AcReims\StatsBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * Classe de gestion des statistiques
 */
interface StatsInterface
{
	/**
	 * Constructeur
	 *
	 * @param $em Gestionnaire d'entités doctrine
	 * @param $session Objet de session
	 */
	public function __construct(EntityManagerInterface $em, Session $session);
 
	/**
	 * Ecrire une entrée statistique corrspondant à ce profil
	 *
	 * @param $profil Profil de l'utilisateur
	 */
	public function incStats($profil);

}
