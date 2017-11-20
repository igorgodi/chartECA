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

use AppBundle\Entity\SpoolTache;

use Doctrine\ORM\EntityManagerInterface;

use Synfony\Component\Form\Exception\InvalidArgumentException;

use Psr\Log\LoggerInterface;


/**
 * Objet de gestion des notifications de l'application
 */
class SpoolTaches
{
	/** Objet logger */
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
	 * Empiler une tache dans la pile
	 *
	 * @param $nomTache Type d'action à traiter dans le spooler
	 * @param $userId Identifiant de l'utilisateur
	 */
	public function push($nomTache, $userId) 
	{
		//--> Vérification des arguments transmis
		if ($nomTache==null || $nomTache=="") throw new InvalidArgumentException("SpoolTaches::push : Le type de demande ne doit pas être null ou vide");
		if ( !(is_numeric($userId)) ) throw new InvalidArgumentException("SpoolTaches::push : La veleur \$userId n'est pas numérique");
		
		//--> Persister en base de données
		$spoolTache = new SpoolTache();
		$spoolTache->setNomTache($nomTache);
		$spoolTache->setUserId($userId);	
		$this->em->persist($spoolTache);
		$this->em->flush();
	}

	/**
	 * Sortir une tache de la pile en l'enlevant après extraction
	 *
	 * @param $nomTache Type d'action à traiter dans le spooler
	 */
	public function pull($nomTache) 
	{
		//--> Vérification des arguments transmis
		if ($nomTache==null || $nomTache=="") throw new InvalidArgumentException("SpoolTaches::pull : Le type de demande ne doit pas être null ou vide");
		
		//--> Récupérer les données : lire uniquement la première entrée du spool en mode FIFO (First In First Out)
		$tache = $this->em->getRepository('AppBundle:SpoolTache')->findOneByNomTache($nomTache, array('id' => 'ASC'));
		// Fin de pile
		if ($tache == null) return (null);
		
		//--> Supprimer la tache du spool
		$this->em->remove($tache);
		$this->em->flush();

		//--> Retourner le résultat
		return ($tache);
	}

	/**
	 * Vider une tache du spool
	 *
	 * @param $nomTache Type d'action à traiter dans le spooler
	 */
	public function vide($nomTache) 
	{
		//--> Vérification des arguments transmis
		if ($nomTache==null || $nomTache=="") throw new InvalidArgumentException("SpoolTaches::vide : Le type de demande ne doit pas être null ou vide");
		
		//--> Traiter le delete
		$tache = $this->em->getRepository('AppBundle:SpoolTache')->deleteTaches($nomTache);
	}

}
