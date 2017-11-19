<?php
/*
 *   Service chargé de gérer les notifications de l'application aux utilisateurs et modérateurs
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

namespace AppBundle\Service;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Synfony\Component\Form\Exception\InvalidArgumentException;

use Psr\Log\LoggerInterface;


/**
 * Objet de gestion des notifications de l'application
 */
class SpoolTaches
{
	/** Oblet logger */
	private $logger;

	/** Gestionnaire d'entité */
	private $em;

	/** Service de journalisation des actions */
	private $journalActions;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->em = $em;
	}

	/**
	 * Empiler une tache dans le spool
	 *
	 * @param $type Type d'action à traiter dans le spooler
	 * @param $user Entité représentant l'utilisateur
	 */
	public function push($type, $user) 
	{
		//--> Vérification des arguments transmis
		if ($type==null || $type=="") throw new InvalidArgumentException("SpoolTaches::push : Le type de demande ne doit pas être null ou vide");
		if ( !($user instanceof User) ) throw new InvalidArgumentException("SpoolTaches::push : L'objet \$user transmis n'est pas du type de l'entité 'User'");
		
		// TODO : Envoyer en base de données


	}

	// TODO : méthode de pull

	// TODO : Méthode de vidage d'un type de tache

}
