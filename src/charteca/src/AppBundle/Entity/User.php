<?php
/*
 *   Entité utilisateurs de chartECA
 * 	Utilisée par le firewall symfony pour gérer la sécurité.
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

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface;


/**
 * User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User implements UserInterface, \Serializable
{
	/********************************************************************************************************/
	/* Enumération du champ etat_compte 									*/
	/********************************************************************************************************/
	const ETAT_COMPTE_INACTIF = 'inactif'; 
	const ETAT_COMPTE_MODERATION = 'moderation'; 
	const ETAT_COMPTE_ACTIF = 'actif'; 
	const ETAT_COMPTE_REVALIDATION_CHARTE = 'revalidation_charte'; 
	
	// Liste des états possibles
	private $_etatCompteValues = array ( 
		self::ETAT_COMPTE_INACTIF, 
		self::ETAT_COMPTE_MODERATION, 
		self::ETAT_COMPTE_ACTIF, 
		self::ETAT_COMPTE_REVALIDATION_CHARTE
	); 
  
	/********************************************************************************************************/
	/* Attributs persistés		 									*/
	/********************************************************************************************************/
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="username", type="string", length=255, unique=true)
	 */
	// TODO : réaliser les relations entre les autres entités
	private $username;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=255)
	 */
	private $email = "";

	/** 
	 * @var string
	 *
	 * @ORM\Column(name="etat_compte", type="string", length=255)
	 */ 
	private $etatCompte = self::ETAT_COMPTE_INACTIF; 

	/**
	* @var date
	*
	* @ORM\Column(name="date_maxi_revalidation_charte", type="date", nullable=true)
	*/
	private $dateMaxiRevalidationCharte;

	/** 
	 * @var string
	 *
	 * @ORM\Column(name="cn", type="string", length=255)
	 */ 
	private $cn; 

	/** 
	 * @var array
	 *
	 * @ORM\Column(name="fonctions", type="array", nullable=true)
	 */ 
	private $fonctions; 

	/** 
	 * @var array
	 *
	 * @ORM\Column(name="etablissements", type="array", nullable=true)
	 */ 
	private $etablissements; 

	/********************************************************************************************************/
	/* Attributs non persistés										*/
	/********************************************************************************************************/
	/** Roles déduits de RSA */
	private $roles = array();

	/********************************************************************************************************/
	/* Implémentatation de l'interface UserInterface							*/
	/********************************************************************************************************/
	/** @see Symfony\Component\Security\Core\User\UserInterface::getUsername() */
	public function getUsername()
	{
		return $this->username;
	}

	/** @see Symfony\Component\Security\Core\User\UserInterface::getRoles() */
	public function getRoles()
	{
		return $this->roles;
	}

	/** @see Symfony\Component\Security\Core\User\UserInterface::getPassword() */
	public function getPassword()
	{
		// Non nécessaire car c'est RSA qui gère l'authentification, attribut password inexistant.
	}

	/** @see Symfony\Component\Security\Core\User\UserInterface::getSalt() */
	public function getSalt()
	{
		// Non nécessaire car c'est RSA qui gère l'authentification, attribut salt inexistant.
	}

	/** @see Symfony\Component\Security\Core\User\UserInterface::eraseCredential() */
	public function eraseCredentials()
	{
		// Non nécessaire car c'est RSA qui gère l'authentification.
	}

	/********************************************************************************************************/
	/* Implémentation de l'interface Serializable								*/
	/********************************************************************************************************/
	/** @see \Serializable::serialize() */
	public function serialize()
	{
		return serialize(array(
		    $this->id,
		    $this->username,
		    $this->email,
		    $this->etatCompte,
		    $this->dateMaxiRevalidationCharte,
		    $this->cn
		));
	}

	/** @see \Serializable::unserialize() */
	public function unserialize($serialized)
	{
		list (
		    $this->id,
		    $this->username,
		    $this->email,
		    $this->etatCompte,
		    $this->dateMaxiRevalidationCharte,
		    $this->cn
		) = unserialize($serialized);
	}

	/********************************************************************************************************/
	/* Autres implémentations que les interfaces UserInterface Serializable					*/
	/********************************************************************************************************/
	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	* Set username
	*
	* @param string $username
	*
	* @return User
	*/
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * Set roles
	 *
	 * @param array $roles
	 *
	 * @return User
	 */
	public function setRoles($roles)
	{
		$this->roles = $roles;

		return $this;
	}

	/** 
	 * Get email 
	 * 
	 * @return string 
	 */ 
	public function getEmail() 
	{ 
		return $this->email; 
	} 

	/** 
	 * Set email 
	 * 
	 * @param string $email 
	 */ 
	public function setEmail($email) 
	{ 
		$this->email = $email; 

		return $this;
	} 

	/** 
	 * Get etatCompte 
	 * 
	 * @return string 
	 */ 
	public function getEtatCompte() 
	{ 
		return $this->etatCompte; 
	} 

	/** 
	 * Set etatCompte 
	 * 
	 * @param string $etatCompte 
	 */ 
	public function setEtatCompte($etatCompte) 
	{ 
		// Le champs doit faire parti des definitions en constantes
		if (!in_array($etatCompte, $this->_etatCompteValues)) 
		{ 
			throw new \InvalidArgumentException( sprintf('Valeur invalide pour User.etatCompte : %s.', $etatCompte) ); 
		} 

		$this->etatCompte = $etatCompte; 

		return $this;
	} 

	/** 
	 * Get cn
	 * 
	 * @return string 
	 */ 
	public function getCn() 
	{ 
		return $this->cn; 
	} 

	/** 
	 * Set cn 
	 * 
	 * @param string $cn 
	 */ 
	public function setCn($cn) 
	{ 
		$this->cn = $cn; 

		return $this;
	} 

	/** 
	 * Get dateMaxiRevalidationCharte
	 * 
	 * @return date
	 */ 
	public function getDateMaxiRevalidationCharte() 
	{ 
		return $this->dateMaxiRevalidationCharte; 
	} 

	/** 
	 * Set dateMaxiRevalidationCharte 
	 * 
	 * @param date $dateMaxiRevalidationCharte 
	 */ 
	public function setDateMaxiRevalidationCharte($dateMaxiRevalidationCharte) 
	{ 
		$this->dateMaxiRevalidationCharte = $dateMaxiRevalidationCharte; 

		return $this;
	} 

	/** 
	 * Get fonctions
	 * 
	 * @return array 
	 */ 
	public function getFonctions() 
	{ 
		return $this->fonctions; 
	} 

	/** 
	 * Set fonctions 
	 * 
	 * @param array $fonctions 
	 */ 
	public function setFonctions($fonctions) 
	{ 
		$this->fonctions = $fonctions; 

		return $this;
	} 

	/** 
	 * Get etablissements
	 * 
	 * @return array 
	 */ 
	public function getEtablissements() 
	{ 
		return $this->etablissements; 
	} 

	/** 
	 * Set etablissements 
	 * 
	 * @param array $etablissements 
	 */ 
	public function setEtablissements($etablissements) 
	{ 
		$this->etablissements = $etablissements; 

		return $this;
	} 

	/********************************************************************************************************/
	/* Testeurs de l'état du compte	pour simplifier la gestion de la sécurité				*/
	/********************************************************************************************************/
	/** 
	 * Retourne true si utilisateur inactif
	 * 
	 * @return boolean 
	 */ 
	public function isEtatInactif()
	{
		return $this->etatCompte == self::ETAT_COMPTE_INACTIF;
	}

	/** 
	 * Retourne true si utilisateur en attente de modération
	 * 
	 * @return boolean 
	 */ 
	public function isEtatModeration()
	{
		return $this->etatCompte == self::ETAT_COMPTE_MODERATION;
	}

	/** 
	 * Retourne true si utilisateur actif
	 * 
	 * @return boolean 
	 */ 
	public function isEtatActif()
	{
		return $this->etatCompte == self::ETAT_COMPTE_ACTIF;
	}

	/** 
	 * Retourne true si utilisateur en revalidation de charte
	 * 
	 * @return boolean 
	 */ 
	public function isEtatRevalidationCharte()
	{
		return $this->etatCompte == self::ETAT_COMPTE_REVALIDATION_CHARTE;
	}

}

