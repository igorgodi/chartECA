<?php
/*
 *   Entité non persistante utilisée pour le formulaire de modération de 
 *	demande d'utilisation de ECA
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


namespace AppBundle\Entity\NoPersist;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Cette classe n'utilise aucune annotation relative à l'ORM (@ORM\.......)
 */
class ValidDemandeUtilisationEcaRefus
{
	/** 
	 * Motif du refus
	 * @Assert\NotBlank(message = "Veuillez saisir un texte")
	 */
	private $motifRefus;

	/**
	 * Constructeur : valeurs par défaut
	 */
	public function __construct()
	{
	}

	/** 
	 * Get motifRefus
	 * 
	 * @return string 
	 */ 
	public function getMotifRefus() 
	{ 
		return $this->motifRefus; 
	} 

	/** 
	 * Set motifRefus 
	 * 
	 * @param string $motifRefus
	 */ 
	public function setMotifRefus($motifRefus) 
	{ 
		$this->motifRefus = $motifRefus; 

		return $this;
	} 
}


