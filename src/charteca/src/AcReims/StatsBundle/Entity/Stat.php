<?php

namespace AcReims\StatsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Stat
 *
 * Voir : http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/annotations-reference.html#annref-uniqueconstraint
 *
 * @ORM\Table(name="Stat", 
 *		uniqueConstraints={
 *        		@UniqueConstraint(name="block", 
 *            			columns={"annee", "mois", "jour", "heure", "profil"}) })
 * @ORM\Entity(repositoryClass="AcReims\StatsBundle\Repository\StatRepository")
 * 
 */
class Stat
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
	* @var int
	*
	* @ORM\Column(name="annee", type="integer")
	*/
	private $annee;

	/**
	* @var int
	*
	* @ORM\Column(name="mois", type="integer")
	*/
	private $mois;

	/**
	* @var int
	*
	* @ORM\Column(name="jour", type="integer")
	*/
	private $jour;

	/**
	* @var int
	*
	* @ORM\Column(name="heure", type="integer")
	*/
	private $heure;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="profil", type="string", length=255)
	 */
	private $profil = "";

	/**
	* @var int
	*
	* @ORM\Column(name="cpt", type="integer")
	*/
	private $cpt;

	/**
	 * Constructeur d'un nouvel objet
	 *
	 * @param $profil Profil utilisateur
	 */
	public function __construct($profil)
	{
		$this->annee = date("Y");
		$this->mois = date("m");
		$this->jour = date("j");
		$this->heure = date("H");
		$this->cpt = 1;
		$this->profil = $profil;
	}

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
	* Set annee
	*
	* @param integer $annee
	*
	* @return Stat
	*/
	public function setAnnee($annee)
	{
		$this->annee = $annee;

		return $this;
	}

	/**
	* Get annee
	*
	* @return int
	*/
	public function getAnnee()
	{
		return $this->annee;
	}

	/**
	* Set mois
	*
	* @param integer $mois
	*
	* @return Stat
	*/
	public function setMois($mois)
	{
		$this->mois = $mois;

		return $this;
	}

	/**
	* Get mois
	*
	* @return int
	*/
	public function getMois()
	{
		return $this->mois;
	}

	/**
	* Set jour
	*
	* @param integer $jour
	*
	* @return Stat
	*/
	public function setJour($jour)
	{
		$this->jour = $jour;

		return $this;
	}

	/**
	* Get jour
	*
	* @return int
	*/
	public function getJour()
	{
		return $this->jour;
	}

	/**
	* Set heure
	*
	* @param string $heure
	*
	* @return Stat
	*/
	public function setHeure($heure)
	{
		$this->heure = $heure;

		return $this;
	}

	/**
	* Get heure
	*
	* @return string
	*/
	public function getHeure()
	{
		return $this->heure;
	}

	/**
	* Set profil
	*
	* @param string $profil
	*
	* @return Stat
	*/
	public function setProfil($profil)
	{
		$this->profil = $profil;

		return $this;
	}

	/**
	* Get profil
	*
	* @return string
	*/
	public function getProfil()
	{
		return $this->profil;
	}

	/**
	* Set cpt
	*
	* @param integer $cpt
	*
	* @return Stat
	*/
	public function setCpt($cpt)
	{
		$this->cpt = $cpt;

		return $this;
	}

	/**
	* Get cpt
	*
	* @return integer
	*/
	public function getCpt()
	{
		return $this->cpt;
	}
}
