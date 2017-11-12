<?php
/*
 *   Service de remplissage du journal des actions sur les comptes utilisateurs
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

use AppBundle\Entity\Log;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;


/**
 * Objet de gestion du journal des actions utilisateurs
 */
class JournalActions
{
	/** Gestion des logs */
	private $logger;

	/** Gestionnaire d'entité */
	private $em;

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
	 * Enregistrer une entrée dans le journal
	 *
	 * @param $username Identifiant de l'utilisateur concerné
	 * @param $traitement Nom du traitement réalisé 
	 * @param $message Message à écrire dans le journal
	 */
	public function enregistrer($username, $traitement, $message)
	{
		// On vérifie les erreurs possibles
		if ($username == ""|| $username ==null) { $this->logger->critical("paramètre 'username' vide ou null"); return; }
		if ($traitement == ""|| $traitement ==null) { $this->logger->critical("paramètre 'traitement' vide ou null"); return; }
		if ($message == ""|| $message ==null) { $this->logger->critical("paramètre 'message' vide ou null"); return; }

		// Créer l'entrée dans le journal
		$this->logger->debug("Création d'une entrée dans le journal des actions ('$username' --> '$traitement' --> '$message')");
		$log = new Log();
		$log->setUsername($username);
		$log->setTraitement($traitement);
		$log->setMessage($message);	
		$this->em->persist($log);
		$this->em->flush();
	}

}
?>
