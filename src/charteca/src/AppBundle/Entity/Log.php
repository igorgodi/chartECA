<?php
/*
 *   Entité du journal des actions sur les comptes utilisateurs
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
 * Log
 *
 * @ORM\Table(name="Log")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Log
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
	* @ORM\Column(name="username", type="string", length=255)
	*/
	// TODO : réaliser les relations entre les entités avec User
	private $username;

	/**
	* @var \DateTime
	*
	* @ORM\Column(name="date", type="datetime")
	*/
	private $date;

	/**
	* @var string
	*
	* @ORM\Column(name="message", type="text")
	*/
	private $message;


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
	* @return Log
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
	* Set date
	*
	* @param \DateTime $date
	*
	* @return Log
	*/
	public function setDate($date)
	{
		$this->date = $date;

		return $this;
	}

	/**
	* Get date
	*
	* @return \DateTime
	*/
	public function getDate()
	{
		return $this->date;
	}

	/**
	* Set message
	*
	* @param string $message
	*
	* @return Log
	*/
	public function setMessage($message)
	{
		$this->message = $message;

		return $this;
	}

	/**
	* Get message
	*
	* @return string
	*/
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @ORM\PrePersist
	 */
	public function updateDate()
	{
		$this->setDate(new \Datetime());
	}
}

