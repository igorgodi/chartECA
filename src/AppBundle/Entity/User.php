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

	// Attributs non persistés
	/** Roles déduits de RSA */
	private $roles = array();

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

}

