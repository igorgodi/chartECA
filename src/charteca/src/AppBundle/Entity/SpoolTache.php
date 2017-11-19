<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpoolTache
 *
 * @ORM\Table(name="spool_tache")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SpoolTacheRepository")
 */
class SpoolTache
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
     * @ORM\Column(name="nomTache", type="string", length=255)
     */
    private $nomTache;

    /**
     * @var \stdClass
     *
     * @ORM\Column(name="entite", type="object")
     */
    private $entite;


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
     * Set nomTache
     *
     * @param string $nomTache
     *
     * @return SpoolTache
     */
    public function setNomTache($nomTache)
    {
        $this->nomTache = $nomTache;

        return $this;
    }

    /**
     * Get nomTache
     *
     * @return string
     */
    public function getNomTache()
    {
        return $this->nomTache;
    }

    /**
     * Set entite
     *
     * @param \stdClass $entite
     *
     * @return SpoolTache
     */
    public function setEntite($entite)
    {
        $this->entite = $entite;

        return $this;
    }

    /**
     * Get entite
     *
     * @return \stdClass
     */
    public function getEntite()
    {
        return $this->entite;
    }
}

