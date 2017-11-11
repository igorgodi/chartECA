<?php
/*
 *   Service de lecture dans l'annuaire LDAP
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

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Exception\ConnexionException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;

use Psr\Log\LoggerInterface;

/**
 * Classe de lecture LDAP
 */
class LdapReader
{
	/** Oblet logger */
	private $logger;

	/** Paramètres LDAP */
	private $ldapHost;
	private $ldapPort;
	private $ldapReaderDn;
	private $ldapReaderPw;
	private $ldapRacine;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $ldapHost Hôte LDAP
	 * @param $ldapHost Port LDAP
	 * @param $ldapHost Compte de lecture de l'annuaire LDAP
	 * @param $ldapHost Mot de passe du compte de lecture de l'annuaire LDAP
	 * @param $ldapHost Racine des requêtes dans l'annuaire LDAP
	 */
	public function __construct(LoggerInterface $logger, $ldapHost, $ldapPort, $ldapReaderDn, $ldapReaderPw, $ldapRacine)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->ldapHost = $ldapHost;
		$this->ldapPort = $ldapPort;
		$this->ldapReaderDn = $ldapReaderDn;
		$this->ldapReaderPw = $ldapReaderPw;
		$this->ldapRacine = $ldapRacine;
	}
 
	/**
	 * Enregistrer une entrée dans le journal
	 *
	 * @param $uid Identifiant de l'utilsateur à rechercher
	 * 
	 * @return Fiche LDAP de l'utilisateur
	 */
	// TODO : traiter les exception et journaliser dans logger
	public function getUser($uid)
	{
		$adapter = new Adapter(array(
		    'host' => $this->ldapHost,
		    'port' => $this->ldapPort,
		    'encryption' => 'none',
		    'options' => array(
			'protocol_version' => 2,
			'referrals' => false,
		    ),
		));

		$ldap = new Ldap($adapter);

		$ldap->bind($this->ldapReaderDn, $this->ldapReaderPw);

		$results = $ldap->query($this->ldapRacine,'(uid='.$uid.')')
				->execute()
				->toArray();

		if(!empty($results)) return $results[0];

		return null;

	}
		
	/**
	 * Envoyer une requête LDAP à l'annuaire
	 *
	 * @param $request Requête LDAP
	 * 
	 * @return Tableau des fiches LDAP correspondant à la requête 
	 */
	// TODO : traiter les exception et journaliser dans logger
	public function getRequest($request)
	{
		$adapter = new Adapter(array(
		    'host' => $this->ldapHost,
		    'port' => $this->ldapPort,
		    'encryption' => 'none',
		    'options' => array(
			'protocol_version' => 2,
			'referrals' => false,
		    ),
		));

		$ldap = new Ldap($adapter);

		$ldap->bind($this->ldapReaderDn, $this->ldapReaderPw);

		$results = $ldap->query($this->ldapRacine, $request)
				->execute()
				->toArray();

		if(!empty($results)) return $results;

		return ([]);
	}
		
}
