<?php

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
	* @ORM\Column(name="traitement", type="string", length=255)
	*/
	private $traitement;

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
	* Set traitement
	*
	* @param string $traitement
	*
	* @return Log
	*/
	public function setTraitement($traitement)
	{
		$this->traitement = $traitement;

		return $this;
	}

	/**
	* Get traitement
	*
	* @return string
	*/
	public function getTraitement()
	{
		return $this->traitement;
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

