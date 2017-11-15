<?php
/*
 *   Service d'écriture dans l'annuaire LDAP
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

namespace AppBundle\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;

use Psr\Log\LoggerInterface;


/**
 * Classe d'écriture LDAP
 */
class LdapWriter
{
	/** Oblet logger */
	private $logger;

	/** Paramètres LDAP */
	private $ldapHost;
	private $ldapPort;
	private $ldapWriterDn;
	private $ldapWriterPw;
	private $ldapRacine;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $ldapHost Hôte LDAP
	 * @param $ldapPort Port LDAP
	 * @param $ldapWriterDn Compte de lecture de l'annuaire LDAP
	 * @param $ldapWriterPw Mot de passe du compte de lecture de l'annuaire LDAP
	 * @param $ldapRacine Racine des requêtes dans l'annuaire LDAP
	 */
	public function __construct(LoggerInterface $logger, $ldapHost, $ldapPort, $ldapWriterDn, $ldapWriterPw, $ldapRacine)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->ldapHost = $ldapHost;
		$this->ldapPort = $ldapPort;
		$this->ldapWriterDn = $ldapWriterDn;
		$this->ldapWriterPw = $ldapWriterPw;
		$this->ldapRacine = $ldapRacine;
	}
 
	/**
	 * Ajout d'une entrée dans le champ AttributApplicationLocale locale si elle n'existe pas encore
	 *
	 * @param $username Identifiant ldap de l'utilisateur concerné
	 * @param $appli Nom de l'application à ajouter dans le champ attributapplicationlocale 
	 * @param $profil Profil lié à l'application
	 * @param $param1 Paramètre1 lié à l'application et au profil
	 * @param $param2 Paramètre2 lié à l'application et au profil
	 * 
	 * @throw \Exception en cas d'erreur
	 */
	public function ajoutEntreeAttributApplicationLocale($username, $appli, $profil, $param1, $param2)
	{
		// Tests divers avant de commencer
		if ($username == null || $username == "")  throw new \Exception("Le paramètre username ne doit pas être null ou vide");
		if ($appli == null || $appli == "")  throw new \Exception("Le paramètre appli ne doit pas être null ou vide");
		if ($profil == null || $profil == "")  throw new \Exception("Le paramètre profil ne doit pas être null ou vide");

		// Connexion au serveur
		$ldap = ldap_connect($this->ldapHost, $this->ldapPort);
		if (!$ldap) throw new \Exception("Impossible de se connecter au serveur LDAP ". $this->ldapHost . ":" . $this->ldapPort);

		// Authentification avec le compte writer
		$ldapBind = ldap_bind($ldap, $this->ldapWriterDn, $this->ldapWriterPw);
		if (!$ldapBind) throw new \Exception("Echec bind serveur LDAP ". $this->ldapHost . ":" . $this->ldapPort . " Writer=" . $this->ldapWriterDn);

		// Recherche de l'utilisateur
		$filtre = 'uid=' . $username;
		$read = ldap_search($ldap, $this->ldapRacine, $filtre);
		if (!$ldap) throw new \Exception("Echec à la recherche ". $this->ldapHost . ":" . $this->ldapPort . " racine=" . $this->ldapRacine . " ; req='$filtre'");

		// Récupère les entrées
		$info = ldap_get_entries($ldap, $read);
		if (!$ldap) throw new \Exception("Echec à récupération des entrées de la recherche ". $this->ldapHost . ":" . $this->ldapPort . " racine=" . $this->ldapRacine . " ; req='$filtre'");

		// Si l'utilisateur n'a pas été trouvé on log en error car c'est moins grave mais on retourne tjs false car erreur
		if (!isset($info[0]["dn"])) 
		{
			$this->logger->error("LdapWriter::ajoutEntreeAttributApplicationLocale() : l'utilisateur uid=$username n'a pas été trouvé dans l'annuaire LDAP");
			ldap_close($ldap);
			return;
		}

		// Récupère le DN
		$thisDn = $info[0]["dn"];

		// On va vérifer que cette entrée n'existe pas déjà
		$trouve = false;
		// Il ne faut pas qu'il ait déjà et construit le tableau des nouveaux attributs
		$newTab["attributapplicationlocale"] = [];
		if (isset ($info[0]["attributapplicationlocale"]) && $info[0]["attributapplicationlocale"]["count"] != 0) 
		{
			for ($j=0 ; $j<$info[0]["attributapplicationlocale"]["count"] ; $j++) 
			{
				// Decompacter la chaine
				$decomp = explode("|", $info[0]["attributapplicationlocale"][$j]);
				// Vérifier la presence et la validité des paramètres optionnel
				$paramOK = true;
				if (isset($decomp[2]) && $decomp[2]!=$param1) $paramOK = false;  
				if (isset($decomp[3]) && $decomp[3]!=$param2) $paramOK = false;  
				// Vérifier la validité de l'attribut complet
				if (isset($decomp[0]) && $decomp[0]==$appli && isset($decomp[1]) && $decomp[1]==$profil && $paramOK) $trouve = true;
				// Charge le nouveau tableau des attributs avec toutes les valeurs trouvées
				$newTab["attributapplicationlocale"][] = $info[0]["attributapplicationlocale"][$j];
			}
		}

		// Si elle existe, on peut arrêt
		if ($trouve) 
		{
			$this->logger->notice("LdapWriter::ajoutEntreeAttributApplicationLocale() : Ajout de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' : existe déjà.");
			ldap_close($ldap);
			return;
		}

		// Ajoute la nouvelle entrée au tableau
		$newTab["attributapplicationlocale"][] = "$appli|$profil|$param1|$param2";

		// Modifier l'attribut dans la fiche ldap
		if (!ldap_modify($ldap, $thisDn, $newTab))
		{
			$this->logger->error("LdapWriter::ajoutEntreeAttributApplicationLocale() : l'ajout de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' a echouée");
			ldap_close($ldap);
			throw new \Exception("LdapWriter::ajoutEntreeAttributApplicationLocale() : l'ajout de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' a echouée");
		}

		// Si la réalisation de l'ajout a été un succès 
		$this->logger->notice("LdapWriter::ajoutEntreeAttributApplicationLocale() : Ajout de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' : réalisée");
		ldap_close($ldap);
		return;
	}

	/**
	 * Supprimer une entrée dans le champ AttributApplicationLocale locale si elle existe
	 *
	 * @param $username Identifiant ldap de l'utilisateur concerné
	 * @param $appli Nom de l'application à supprimer dans le champ attributapplicationlocale 
	 * @param $profil Profil lié à l'application
	 * @param $param1 Paramètre1 lié à l'application et au profil
	 * @param $param2 Paramètre2 lié à l'application et au profil
	 * 
	 * @throw \Exception en cas d'erreur
	 */
	public function supprEntreeAttributApplicationLocale($username, $appli, $profil, $param1, $param2)
	{
		// Tests divers avant de commencer
		if ($username == null || $username == "")  throw new \Exception("Le paramètre username ne doit pas être null ou vide");
		if ($appli == null || $appli == "")  throw new \Exception("Le paramètre appli ne doit pas être null ou vide");
		if ($profil == null || $profil == "")  throw new \Exception("Le paramètre profil ne doit pas être null ou vide");
	
		// Connexion au serveur
		$ldap = ldap_connect($this->ldapHost, $this->ldapPort);
		if (!$ldap) throw new \Exception("Impossible de se connecter au serveur LDAP ". $this->ldapHost . ":" . $this->ldapPort);

		// Authentification avec le compte writer
		$ldapBind = ldap_bind($ldap, $this->ldapWriterDn, $this->ldapWriterPw);
		if (!$ldapBind) throw new \Exception("Echec bind serveur LDAP ". $this->ldapHost . ":" . $this->ldapPort . " Writer=" . $this->ldapWriterDn);

		// Recherche de l'utilisateur
		$filtre = 'uid=' . $username;
		$read = ldap_search($ldap, $this->ldapRacine, $filtre);
		if (!$ldap) throw new \Exception("Echec à la recherche ". $this->ldapHost . ":" . $this->ldapPort . " racine=" . $this->ldapRacine . " ; req='$filtre'");

		// Récupère les entrées
		$info = ldap_get_entries($ldap, $read);
		if (!$ldap) throw new \Exception("Echec à récupération des entrées de la recherche ". $this->ldapHost . ":" . $this->ldapPort . " racine=" . $this->ldapRacine . " ; req='$filtre'");

		// Si l'utilisateur n'a pas été trouvé on log en error car c'est moins grave mais on retourne tjs false car erreur
		if (!isset($info[0]["dn"])) 
		{
			$this->logger->error("LdapWriter::supprEntreeAttributApplicationLocale() : l'utilisateur uid=$username n'a pas été trouvé dans l'annuaire LDAP");
			ldap_close($ldap);
			return;
		}

		// Récupère le DN
		$thisDn = $info[0]["dn"];

		// On va vérifer que cette entrée existe bien
		$trouve = false;
		// Il faut déjà qu'il ai cet attribut et construit le tableau des nouveaux attributs avec en mojns celui supprimé
		$newTab["attributapplicationlocale"] = [];
		if (isset ($info[0]["attributapplicationlocale"]) && $info[0]["attributapplicationlocale"]["count"] != 0) 
		{
			for ($j=0 ; $j<$info[0]["attributapplicationlocale"]["count"] ; $j++) 
			{
				// Decompacter la chaine
				$decomp = explode("|", $info[0]["attributapplicationlocale"][$j]);
				// Vérifier la presence et la validité des paramètres optionnel
				$paramOK = true;
				if (isset($decomp[2]) && $decomp[2]!=$param1) $paramOK = false;  
				if (isset($decomp[3]) && $decomp[3]!=$param2) $paramOK = false;  
				// Si c'est l'attribut recherché, placer comme trouvé mais ne pas ajouter au tableau final
				if (isset($decomp[0]) && $decomp[0]==$appli && isset($decomp[1]) && $decomp[1]==$profil && $paramOK) $trouve = true;
				// Sinon remplit le tableau avec les attributs à recopier
				else $newTab["attributapplicationlocale"][] = $info[0]["attributapplicationlocale"][$j];
			}
		}

		// Si elle existe, on peut arrêt
		if (!$trouve) 
		{
			$this->logger->notice("LdapWriter::supprEntreeAttributApplicationLocale() : Suppression de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' : n'existe pas.");
			ldap_close($ldap);
			return;
		}

		// Modifier l'attribut dans la fiche ldap
		if (!ldap_modify($ldap, $thisDn, $newTab))
		{
			$this->logger->error("LdapWriter::supprEntreeAttributApplicationLocale() : Suppression de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' a echouée");
			ldap_close($ldap);
			throw new \Exception("LdapWriter::supprEntreeAttributApplicationLocale() : Suppression de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' a echouée");
		}

		// Si la réalisation de la suppression a été un succès 
		$this->logger->notice("LdapWriter::supprEntreeAttributApplicationLocale() : Suppression de l'entrée '$appli|$profil|$param1|$param2' pour l'utilisateur dn='$thisDn' : réalisée");
		ldap_close($ldap);
		return;
 	}	
}
