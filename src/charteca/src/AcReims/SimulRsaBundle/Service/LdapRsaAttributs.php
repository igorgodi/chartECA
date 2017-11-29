<?php 
/*
 *   Service de conversion des attributs LDAP vers RSA
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

namespace AcReims\SimulRsaBundle\Service;

use AcReims\SimulRsaBundle\Service\LdapReader;

/**
 * Service de construction des attributs RSA à partir d'une requête LDAP
 */
class LdapRsaAttributs
{
	/** Service Ldap */
	private $ldap;

	/** Définition des attributs RSA et leur correspondance LDAP */
	private $attributeDefinitions = array(
		'ct-remote-user'		=> array('ldapName'   => 'uid',				'multivalue'    => false),
		'cn'            		=> array('ldapName'   =>  'cn',				'multivalue'    => false),
		'ctln'            		=> array('ldapName'   =>  'sn',				'multivalue'    => false),
		'ctfn'     			=> array('ldapName'   =>  'givenName',			'multivalue'    => false),
		'ctemail'          		=> array('ldapName'   =>  'mail',			'multivalue'    => false),
		'ctdn'				=> array('ldapName'   =>  'dn',				'multivalue'    => false),
		'employeeNumber'		=> array('ldapName'   =>  'numen',			'multivalue'    => false),
		'rne'				=> array('ldapName'   =>  'rne',			'multivalue'    => false),
		'typensi'			=> array('ldapName'   =>  'typensi',			'multivalue'    => false),
		'title'				=> array('ldapName'   =>  'title',			'multivalue'    => false),
		'grade'				=> array('ldapName'   =>  'grade',			'multivalue'    => false),
		'datenaissance'			=> array('ldapName'   =>  'datenaissance',		'multivalue'    => false),
		'codecivilite'			=> array('ldapName'   =>  'codecivilite',		'multivalue'    => false),
		'frEdufonctadm'			=> array('ldapName'   =>  'FrEduFonctAdm',		'multivalue'    => false),
		'ctgrps'			=> array('ldapName'   =>  'ctgrps',			'multivalue'    => true),
		'freduresdel'			=> array('ldapName'   =>  'FrEduResDel',		'multivalue'    => true),
		'fredugestresp'			=> array('ldapName'   =>  'FrEduGestResp',		'multivalue'    => true),
		'fredurne'			=> array('ldapName'   =>  'FrEduRne',			'multivalue'    => true),
		'fredurneresp'			=> array('ldapName'   =>  'FrEduRneResp',		'multivalue'    => true),
		'attributapplicationlocale'	=> array('ldapName'   =>  'AttributApplicationLocale',	'multivalue'    => true)
	);

	/** Tableau des attributs RSA relevés */
	private $attributes = [];

	/**
	 * Constructeur
	 *
	 * @param LdapReader Objet de lecture Ldap
	 */
	public function __construct (LdapReader $ldap)
	{
		$this->ldap = $ldap;
	}

	/**
	 * Récupération de la liste des clés attibuts RSA simulés 
	 *
	 * @return Array
	 */
	public function getAttributesKeys()
	{
		return (array_keys($this->attributeDefinitions));
	}

	/** 
	 * Retourne la liste des attributs RSA lus dans une fiche ldap
	 *
	 * @param $user Identifiant ldap et rsa de l'utilisateur
	 *
	 * @return Array tableau de valeur ou null si utilisateur non trouvé
	 */
	public function getAttributsRsaLdap($user)
	{
		// RAZ tableau
		$this->attributes = [];

		// Recherche
		if ( ($record = $this->ldap->getUser($user)) == null) return(null);

		// Conversion champs RSA par champ RSA
		foreach ($this->attributeDefinitions as $key => $value)
		{
			// Cas particulier du dn
			if ($key == "ctdn") 
			{
				$this->attributes[$key] = $record->getDn();
				continue;
			}

			if (!$value['multivalue']) 
			{ 
				if ($record->getAttribute($value['ldapName'])[0] != null) $this->attributes[$key] = $record->getAttribute($value['ldapName'])[0]; 
			}
			else
			{ 
				if ($record->getAttribute($value['ldapName']) != null) $this->attributes[$key] = implode (",", $record->getAttribute($value['ldapName']));
			}
		}

		// retourne le tableau
		return($this->attributes);
	}
	

}

?>
