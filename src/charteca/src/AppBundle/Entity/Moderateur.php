<?php
/*
 *   Entité modérateurs de ChartECA
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

/**
 * Moderateur
 *
 * @ORM\Table(name="Moderateur")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ModerateurRepository")
 */
class Moderateur
{
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
	// TODO : réaliser les relations entre les entités avec User
	private $username;

	/**
	* @var string
	*
	* @ORM\Column(name="email", type="string", length=255, unique=true)
	*/
	private $email;


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
	* @return Moderateur
	*/
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	* Get username
	*
	* @return string
	*/
	public function getUsername()
	{
		return $this->username;
	}

	/**
	* Set email
	*
	* @param string $email
	*
	* @return Moderateur
	*/
	public function setEmail($email)
	{
		$this->email = $email;

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
}

