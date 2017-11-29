<?php
/*
 *   Service chargé de gérer les actions de base sur les utilisateurs
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
 * Objet de gestion des utilisateurs de l'application
 */
class GestUser
{
	/** Oblet logger */
	private $logger;

	/** Gestionnaire d'entité */
	private $em;

	/** Delai avant blocage en passe en revalidation charte */
	private $delaiRevalidation;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(LoggerInterface $logger, EntityManagerInterface $em, $delaiRevalidation)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->em = $em;
		$this->delaiRevalidation = $delaiRevalidation;
	}

	/**
	 * Passer un utilisateur en état de compte 'User::ETAT_COMPTE_INACTIF'
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function etatCompteInactif($user) 
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("GestUser::etatCompteInactif() : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> On passe en mode inactif et on annule la date de revalidation
		$user->setEtatCompte(User::ETAT_COMPTE_INACTIF);
		$user->setDateMaxiRevalidationCharte(null);	

		//--> Enregistrement de l'utilisateur
		$this->em->persist($user);
		$this->em->flush();
	}


	/**
	 * Passer un utilisateur en état de compte 'User::ETAT_COMPTE_MODERATION'
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function etatCompteAttenteActivation($user) 
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("GestUser::etatCompteActivation() : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> On passe en mode attente modération et on annule la date de revalidation
		$user->setEtatCompte(User::ETAT_COMPTE_MODERATION);
		$user->setDateMaxiRevalidationCharte(null);	

		//--> Enregistrement de l'utilisateur
		$this->em->persist($user);
		$this->em->flush();
	}


	/**
	 * Passer un utilisateur en état de compte 'User::ETAT_COMPTE_ACTIF'
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function etatCompteActif($user) 
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("GestUser::etatCompteActif() : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> On passe en mode actif et on annule la date de revalidation
		$user->setEtatCompte(User::ETAT_COMPTE_ACTIF);
		$user->setDateMaxiRevalidationCharte(null);	

		//--> Enregistrement de l'utilisateur
		$this->em->persist($user);
		$this->em->flush();
	}


	/**
	 * Passer un utilisateur en état de compte 'User::ETAT_COMPTE_REVALIDATION_CHARTE'
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function etatCompteRevalidationCharte($user) 
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("GestUser::etatCompteRevalidationCharte() : L'objet \$user transmis n'est pas du type de l'entité 'User'");
		if ( !is_numeric($this->delaiRevalidation) ) throw new InvalidArgumentException("GestUser::etatCompteRevalidationCharte() : La valeur \$this->delaiRevalidation doit-être numérique");
		if ( $this->delaiRevalidation==0 ) throw new InvalidArgumentException("GestUser::etatCompteRevalidationCharte() : La valeur \$this->delaiRevalidation ne doit pas être nul");

		//--> On force la revalidation	
		$user->setEtatCompte(User::ETAT_COMPTE_REVALIDATION_CHARTE);

		//--> On laisse un délai de $this->delaiRevalidation jours pour revalider la charte
		$maxDate = new\DateTime();
		$maxDate->add(new \DateInterval("P" . $this->delaiRevalidation . "D"));
		$maxDate->format("Y-m-d");
		$user->setDateMaxiRevalidationCharte($maxDate);	

		//--> Enregistrement de l'utilisateur
		$this->em->persist($user);
		$this->em->flush();
	}

}
