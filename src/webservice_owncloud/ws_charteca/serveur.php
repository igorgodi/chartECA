<?php
/*
 *   Serveur SOAP chargé de gérer les modifications dans la base d'owncloud
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


//--> Chargement du fichier de configuration et vérification du contenu nécessaire à la configuration du serveur SOAP
$conf = "config.inc.php";
if (!file_exists($conf)) sendError("Le fichier de configuration '$conf' n'existe pas.");
require $conf;
// Paramètres relatifs à la configuration du serveur SOAP.
if (!defined("SOAP_SERVEUR_ADDR")) sendError("La constante 'SOAP_SERVEUR_ADDR' n'est pas définie dans 'config.inc.php'");
if (!defined("SOAP_SERVEUR_HTTPS_SECURE")) sendError("La constante 'SOAP_SERVEUR_HTTPS_SECURE' n'est pas définie dans 'config.inc.php'");
if (!defined("SOAP_SERVEUR_LIMIT_IP")) sendError("La constante 'SOAP_SERVEUR_LIMIT_IP' n'est pas définie dans 'config.inc.php'");
if (!defined("SOAP_SERVEUR_TOKEN")) sendError("La constante 'SOAP_SERVEUR_TOKEN' n'est pas définie dans 'config.inc.php'");

//--> Chargement de la librairie permettant au serveur de fournir les méthodes métier aux client SOAP
$classe = "OwncloudWs.class.php";
if (!file_exists($classe)) sendError("Le fichier '$classe' contenant la classe métier n'existe pas.");
require $classe;

//--> Sécurité d'accès au serveur
// Test d'encapsulation https : doit-être pris en charge dans apache.conf
if (SOAP_SERVEUR_HTTPS_SECURE && $_SERVER['HTTPS']!="on") sendError("Utilisation obligatoire du protocole https");
// Test d'IP du demandeur : doit-être pris en charge dans apache.conf
if ($_SERVER["SERVER_ADDR"] != SOAP_SERVEUR_LIMIT_IP) sendError("IP Non autorisée (" . $_SERVER["SERVER_ADDR"] . ")");
// Test de la clé de sécurité entre client et serveur
if (!isset($_GET["token"]) || $_GET["token"] != SOAP_SERVEUR_TOKEN) sendError("TOKEN inconnu");

//--> Gestion des exceptions du serveur
// Capture de toutes les exceptions du serveur dans la fonction exception_handler(); afin de les rediriger vers le client
set_exception_handler('exception_handler');

//--> Création du serveur SOAP
// Création du serveur. Ici on n'utilise pas de déclaration WSDL donc on doit déclarer en dur l'adresse
$server = new SoapServer(null, array('uri' => SOAP_SERVEUR_ADDR));
// Classe chargée du traitement du traitement métier
$server->setClass("OwnCloudWs");
// Lancement du traitement
$server->handle();


/********************************************************************************************************************************/
/* fonctions nécessaires au programme principal											*/
/********************************************************************************************************************************/
/**
 * Gestionnaire d'exceptions du serveur
 *
 * @param $exception Contenu de l'exception à envoyer au client
 */
function exception_handler($exception) {
	// Renvoie le message d'exception au client sous forme d'une erreur SOAP	
	sendError($exception->getMessage());
}

/**
 * Envoi d'un message d'erreur SOAP en réponse à la demande du client
 *
 * @param $ex Contenu du message d'erreur
 */
function sendError($ex) {
	//--> Si jamais le message d'erreur transmis est vide, on place un message générique
	if ($ex) {
		$info = $ex;
	} else {
		$info = 'Unknown error';
	}

	//--> Création du retour d'erreur en XML
	$dom = new DOMDocument('1.0', 'UTF-8');
	// Noeud erreur
	$fault = $dom->createElement('SOAP-ENV:Fault');
	// Code erreur
	$fault->appendChild($dom->createElement('faultcode', 'OwncloudWs:error'));
	// Faultstring node.
	$fault->appendChild($dom->createElement('faultstring', $info));
	// Body node.
	$body = $dom->createElement('SOAP-ENV:Body');
	$body->appendChild($fault);
	// Envelope node.
	$envelope = $dom->createElement('SOAP-ENV:Envelope');
	$envelope->setAttribute('xmlns:SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
	$envelope->appendChild($body);
	$dom->appendChild($envelope);
	// Transformation en XML
	$response = $dom->saveXML();
	// Envoi de l'entête du message
	send_headers($response);
	// Envoi du message d'erreur
	echo $response;
	// Fin
	die;
}

/**
 * Envoi de l'entête de réponse au client
 *
 * @param $reponse Contenu de la réponse afin de définir la taille de celle-ci
 */
function send_headers($reponse) {
	header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
	header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
	header('Pragma: no-cache');
	header('Accept-Ranges: none');
	header('Content-Length: ' . strlen($reponse));
	header('Content-Type: application/xml; charset=utf-8');
	header('Content-Disposition: inline; filename="response.xml"');
}

?>
