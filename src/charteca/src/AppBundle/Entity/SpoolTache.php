<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpoolTache
 *
 * @ORM\Table(name="SpoolTache")
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
     * @var int
     *
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;


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
     * Set userId
     *
     * @param int $userId
     *
     * @return SpoolTache
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}

