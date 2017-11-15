<?php
/*
 *   Utilitaire chargé de gérer les connexions SOAP
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

use Psr\Log\LoggerInterface;


/**
 * Objet de gestion des appels SOAP
 */
class ClientSoap
{
	/** Oblet logger */
	private $logger;

	/** Adresse du serveur SOAP */
	private $addr;

	/** Clé d'accès */
	private $token;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $addr Adresse du serveur
	 * @param $token Token d'accès sur le serveur SOAP
	 */
	public function __construct(LoggerInterface $logger, $addr, $token)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->addr = $addr;
		$this->token = $token;
	}

	/**
	 * Initialisation des paramètres serveur SOAP : permet de changer de serveur hors après instanciation du service
	 *
	 * @param $addr Adresse du serveur
	 * @param $token Token d'accès sur le serveur SOAP
	 */
	public function initServeur($addr, $token)
	{
		$this->addr = $addr;
		$this->token = $token;
	}

	/**
	 * Appel au serveur SOAP
	 *
	 * @param $fonction Methode demandé sur le serveur SOAP
	 * @param $params Paramètres de la méthode demandée sur le serveur SOAP
	 *
	 * @return Réponse de la méthode lancée sur le serveur
	 * 
	 * @throw \Exception en cas d'erreur
	 */
	public function appel($fonction, $params) 
	{
		try {
			// Appel client SOAP
			$clientSOAP = new \SoapClient( null,
			array (
				'uri' => $this->addr . '?token=' . $this->token,
				'location' => $this->addr . '?token=' . $this->token,
				'trace' => 1,
				'exceptions' => 1
			));
			$retour = $clientSOAP->__soapCall($fonction, $params);
			// Uniquement en DEVEL : Affiche les détails de chaque transaction
			/*print "---------------------------------------\n";
			print "Devel SOAP :\n";
			print "---------------------------------------\n";
			print "Entête requête :\n";
			print $clientSOAP->__getLastRequestHeaders();
			print "---------------------------------------\n";
			print "Requête :\n";
			print $clientSOAP->__getLastRequest();
			print "---------------------------------------\n";
			print "Entête réponse :\n";
			print $clientSOAP->__getLastResponseHeaders();
			print "---------------------------------------\n";
			print "Réponse :\n";
			print $clientSOAP->__getLastResponse();
			print "---------------------------------------\n";
			print "Rétour :\n";
			print_r($retour);
			print "---------------------------------------\n";*/
			// Retourne le résultat
			return($retour);
		}
		// En cas d'erreur SOAP, on interrompt le process complet et on loggue
		catch(\SoapFault $f) {
			// Journalise l'erreur
			// Message bref
			$this->logger->critical("ClientSoap::appel --> $this->addr : méthode $fonction() : SoapFault() : " . $f->getMessage());
			// Les détails
			$this->logger->debug("ERREUR SOAP #1 : Entête requête  : " . $clientSOAP->__getLastRequestHeaders());
			$this->logger->debug("ERREUR SOAP #2 : Requête : " . $clientSOAP->__getLastRequest());
			$this->logger->debug("ERREUR SOAP #3 : Entête réponse : " . $clientSOAP->__getLastResponseHeaders());
			$this->logger->debug("ERREUR SOAP #4 : Réponse : " . $clientSOAP->__getLastResponse());
			$this->logger->debug("ERREUR SOAP #5 : SoapFault : " . $f);
			// On retourne génère une exception
			throw new \Exception("Erreur client SOAP voir journal symfony");
		}
	}
}
