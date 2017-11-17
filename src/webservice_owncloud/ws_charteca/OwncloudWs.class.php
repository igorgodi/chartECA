<?php
/*
 *   Librairie chargée de gérer les modifications dans les bases moodle
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


/**
 * Classe dédiée à la gestion de la base owncloud pour l'application ChartECA
 */
class OwnCloudWs
{
	/**
	 * Constructeur de la class
	 *
	 * @throws Exception en cas d'erreur dans le fichier de configuration
	 */
	public function __construct()
	{
		// Chargement direct du fichier de configuration d'owncloud
		if (!file_exists("../config/config.php")) throw new Exception("Le fichier de configuration d'owncloud n'a pas été trouvé !");
		require("../config/config.php");

		//--> Configuration d'accès à la base de données de Owncloud
		if (!isset($CONFIG["dbhost"])) 	throw new Exception("La valeur \$CONFIG['dbhost'] n'est pas définie dans le fichier de configuration d'owncloud'."); 
		if (!isset($CONFIG["dbuser"])) 	throw new Exception("La valeur \$CONFIG['dbuser'] n'est pas définie dans le fichier de configuration d'owncloud'."); 
		if (!isset($CONFIG["dbpassword"])) 	throw new Exception("La valeur \$CONFIG['dbpassword'] n'est pas définie dans le fichier de configuration d'owncloud'."); 
		if (!isset($CONFIG["dbname"])) 	throw new Exception("La valeur \$CONFIG['dbname'] n'est pas définie dans le fichier de configuration d'owncloud'."); 
		if (!isset($CONFIG["dbtableprefix"])) 	throw new Exception("La valeur \$CONFIG['dbtableprefix'] n'est pas définie dans le fichier de configuration d'owncloud'."); 

		define ("OWNCLOUD_HOST", $CONFIG["dbhost"]);
		define ("OWNCLOUD_PORT", "3306");
		define ("OWNCLOUD_USER", $CONFIG["dbuser"]);
		define ("OWNCLOUD_PASS", $CONFIG["dbpassword"]);
		define ("OWNCLOUD_DB", $CONFIG["dbname"]);
		define ("OWNCLOUD_PREFIX", $CONFIG["dbtableprefix"]);


		//--> Vérification de la configuration nécessaire à l'accès à la base de données
		if (!defined("OWNCLOUD_HOST")) 	throw new Exception("La constante 'OWNCLOUD_HOST' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
		if (!defined("OWNCLOUD_PORT")) 	throw new Exception("La constante 'OWNCLOUD_PORT' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
		if (!defined("OWNCLOUD_USER")) 	throw new Exception("La constante 'OWNCLOUD_USER' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
		if (!defined("OWNCLOUD_PASS")) 	throw new Exception("La constante 'OWNCLOUD_PASS' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
		if (!defined("OWNCLOUD_DB")) 	throw new Exception("La constante 'OWNCLOUD_DB' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
		if (!defined("OWNCLOUD_PREFIX")) 	throw new Exception("La constante 'OWNCLOUD_PREFIX' n'est pas définie dans 'config.inc.php du webservice ws_charteca'.");
	}

	/**
	 * Retourne la configuration
	 *
	 * @return Tableau de configuration
	 *
	 * @throws Exception en cas d'erreur de traitement
	 */
	public function hello()
	{
		//--> Préparation du tableau 
		$ret = array();

		//--> Tentative de connexion pour vérifier si la configuration est OK
		$mysqli = new mysqli(OWNCLOUD_HOST, OWNCLOUD_USER, OWNCLOUD_PASS, OWNCLOUD_DB, OWNCLOUD_PORT);
		if ($mysqli->connect_error) throw new Exception("MoodelImport::hello(...) SQL : Erreur de connexion : " . $mysqli->connect_errno . " (" . $mysqli->connect_error . ")");

		//--> Si c'est OK, on retourne les champs nécessaires	
		// TODO : autres trucs à passer ??? : voir comment passer un objet....
		//$ret['ok'] = true;
		$ret['ok'] = "Webservice fonctionnel";
		//--> Fermeture de connection
		$mysqli->close();

		//--> Retourne un compte rendu
		return ($ret);
	}

	/**
	 * Retourne .................
	 *
	 * @return ...........
	 *
	 * @throws Exception en cas d'erreur de traitement
	 */
	/*public function listeUtilisateurs()
	{
		//--> Préparation du tableau de la liste des utilisateurs
		$ret = array();

		//--> Connexion à la base d'alimentation
		$mysqli = new mysqli(OWNCLOUD_HOST, OWNCLOUD_USER, OWNCLOUD_PASS, OWNCLOUD_DB, OWNCLOUD_PORT);
		if ($mysqli->connect_error) throw new Exception("OwncloudWs::listeUtilisateurs(...) SQL : Erreur de connexion #1 : " . $mysqli->connect_errno . " (" . $mysqli->connect_error . ")");

		//--> Liste ......
		if (!($stmt = $mysqli->prepare("SELECT 	" . OWNCLOUD_PREFIX . "user.id, 
							" . OWNCLOUD_PREFIX . "user.username, 
							" . OWNCLOUD_PREFIX . "user.email,
							" . OWNCLOUD_PREFIX . "user_info_data.userid, 
							" . OWNCLOUD_PREFIX . "user_info_data.data, 
							" . OWNCLOUD_PREFIX . "role_assignments.contextid 
						FROM 	" . OWNCLOUD_PREFIX . "user, 
							" . OWNCLOUD_PREFIX . "user_info_data,
							" . OWNCLOUD_PREFIX . "role_assignments
							
						WHERE 		" . OWNCLOUD_PREFIX . "user_info_data.userid = " . OWNCLOUD_PREFIX . "role_assignments.userid 
							AND 	" . OWNCLOUD_PREFIX . "user_info_data.userid = " . OWNCLOUD_PREFIX . "user.id 
							AND 	" . OWNCLOUD_PREFIX . "user.deleted = 0 
							AND 	" . OWNCLOUD_PREFIX . "user.timecreated!=0 
							AND 	" . OWNCLOUD_PREFIX . "user_info_data.data like '%" . OWNCLOUD_RNE . "%' 
						GROUP BY 	" . OWNCLOUD_PREFIX . "user.id,
								" . OWNCLOUD_PREFIX . "user_info_data.data, 
								" . OWNCLOUD_PREFIX . "role_assignments.contextid
									") )) throw new Exception("OwncloudWs::listeUtilisateurs(...) SQL : Erreur préparation requête #1");
		if (!$stmt->bind_result($id, $username, $email, $userid, $data, $contextid)) throw new Exception("OwncloudWs::listeUtilisateurs(...) SQL : Erreur bind_result #1");
		if (!$stmt->execute()) throw new Exception("OwncloudWs::listeUtilisateurs(...) SQL : Erreur execute #1 : " . $stmt->errno . "(" . $stmt->error . ")");
		if (!$stmt->store_result()) throw new Exception("OwncloudWs::listeUtilisateurs(...) SQL : Erreur store_result #1");
		while ($stmt->fetch()) $ret[] = array("id" => $id, "username" => $username, "email" => $email, "userid" => $userid, "data" => $data, "contextid" => $contextid);
		$stmt->close();

		//--> Fermeture de la connexion
		$mysqli->close();

		//--> Retourne un compte rendu
		return ($ret);
	}*/


}

?>
