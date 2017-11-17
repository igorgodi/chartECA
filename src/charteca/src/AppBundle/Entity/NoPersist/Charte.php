<?php
/*
 *   Entité non persistante utilisée pour le formulaire de publication 
 *	de la charte ECA
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
class Charte
{
	/**
	* Fichier à télécharger
	*
	* @Assert\NotBlank(message="Merci d'envoyer un fichier au format pdf")
	* @Assert\File(mimeTypes={ "application/pdf", "application/x-pdf" })
	*/
	private $file;

	/** 
	 *
	 * Destiné à la checkbox, je confirme le dépô
	 * @Assert\IsTrue(message = "Veuillez cocher cette case pour confirmer")
	 */
	private $validation = false;

	/**
	 * Constructeur : valeurs par défaut
	 */
	public function __construct()
	{
	}

	/** 
	 * Get file
	 * 
	 * @return string 
	 */ 
	public function getFile() 
	{ 
		return $this->file; 
	} 

	/** 
	 * Set file 
	 * 
	 * @param string $file
	 */ 
	public function setFile($file) 
	{ 
		$this->file = $file; 

		return $this;
	} 
	/** 
	 * Get validation
	 * 
	 * @return boolean 
	 */ 
	public function getValidation() 
	{ 
		return $this->validation; 
	} 

	/** 
	 * Set validation
	 * 
	 * @param boolean $validation
	 */ 
	public function setValidation($validation) 
	{ 
		$this->validation = $validation; 

		return $this;
	} 
}


