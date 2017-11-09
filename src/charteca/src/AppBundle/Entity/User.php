<?php

//TODO : comment

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
	// Enumération du champ etat_compte 
	const ETAT_COMPTE_INACTIF = 'inactif'; 
	const ETAT_COMPTE_ATTENTE_ACTIVATION = 'en_attente_activation'; 
	const ETAT_COMPTE_ACTIF = 'actif'; 

	private $_etatCompteValues = array ( 
	   self::ETAT_COMPTE_INACTIF, self::ETAT_COMPTE_ATTENTE_ACTIVATION, self::ETAT_COMPTE_ACTIF 
	); 
  
	// TODO : améliorer les séparation attributs, getter etc....
	// Attributs persistés
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

	// Attributs non persistés
	/** Roles déduits de RSA */
	private $roles = array();

	/** Nom complet de l'utilisateur */
	private $cn = "";

	// Implémentation de UserInterface
	// TODO : comment @inherit 
	public function getUsername()
	{
		return $this->username;
	}

	// TODO : comment @inherit 
	public function getRoles()
	{
		return $this->roles;
	}

	// TODO : comment @inherit 
	public function getPassword()
	{
	}

	// TODO : comment @inherit 
	public function getSalt()
	{
	}

	// TODO : comment @inherit 
	public function eraseCredentials()
	{
	}

	// Implémentation de Serializable
	/** @see \Serializable::serialize() */
	public function serialize()
	{
		return serialize(array(
		    $this->id,
		    $this->username,
		));
	}

	/** @see \Serializable::unserialize() */
	public function unserialize($serialized)
	{
		list (
		    $this->id,
		    $this->username,
		) = unserialize($serialized);
	}

	// Autres implémentations que UserInterface et serializable
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


}

