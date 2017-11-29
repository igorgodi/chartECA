<?php
/*
 *   Ecouteurs de remplissage des attributs simulés et de la barre de l'application SimulRsa
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

namespace AcReims\SimulRsaBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Routing\Router;

/**
 * Classe de gestion des écouteurs
 */
class SimulRsa
{
	/** Objet de session */
	private $session;

	/** Objet router */
	private $router;


	/**
	 * Constructeur
	 *
	 * @param $session Objet de session
	 * @param $router Objet de routage
	 */
	public function __construct(Session $session, Router $router)
	{
		// Sauvegarde des objets
		$this->session = $session;
		$this->router = $router;
	}

	/**
	 * Gestion de la substitution des variables RSA en mode simulation
	 *
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event)
	{
		//--> On récupère la requête
		$request = $event->getRequest();

		// TODO créer la page pour charger ceci
		//$this->session->set("_simulRsa_values", array("ct-remote-user" => "toto", "ctemail" => "toto@ac-reims.fr"));
		// TODO : simule un RSA non simulé
		//$request->headers->set("ct-remote-user", "igodi");
		//$request->headers->set("ctemail", "igor.godi@ac-reims.fr");

		//--> Traitement de la simulation RSA uniquement actif dans les bundles AppBundle et IG\TestBundle
		// TODO : pouvoir configurer cette liste
		$controller = $request->attributes->get('_controller');
		if (	   !preg_match("/^AppBundle/", $controller)
			//&& !preg_match("/^IG\\\\TestBundle/", $controller)
			) return;

		//--> Simulation des attributs RSA extrait de la variable  de session '_simulRsa_values' si disponible
		if (count($this->session->get("_simulRsa_values"))!=0) foreach ($this->session->get("_simulRsa_values") as $key => $value) $request->headers->set($key, $value);
	}

	/**
	 * Gestion de l'afichage de la barre SimulRsa
	 *
	 * @param FilterResponseEvent $event
	 */
	// TODO : prevoir moyen de la cacher via une var de session pilotée par le gestionnaire
	public function onKernelResponse(FilterResponseEvent $event)
	{
		//--> On teste si la requête est bien la requête principale (et non une sous-requête)
		if (!$event->isMasterRequest()) return;

		//--> Récupère la requête et la réponse correspondant
		$request = $event->getRequest();
		$response = $event->getResponse();

		//--> Ne pas modifier les redirections, les requêtes autre que HTML, les pièces jointes, ......
		if (	$request->isXmlHttpRequest()
		    ||	$response->isRedirection()
		    || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
		    || 'html' !== $request->getRequestFormat()
		    || false !== stripos($response->headers->get('Content-Disposition'), 'attachment;')
		) return;

		//--> Affichage uniquement actif dans les bundles AppBundle et IG\TestBundle
		// TODO : pouvoir configurer cette liste
		$controller = $request->attributes->get('_controller');
		if (	   !preg_match("/^AppBundle/", $controller)
			//&& !preg_match("/^IG\\\\TestBundle/", $controller)
			) return;

		//--> affichage du ct-remote-user simulé ou réel
		if (count($this->session->get("_simulRsa_values"))!=0) $val = "  : ct-remote-user simulé = '" .$this->session->get("_simulRsa_values")["ct-remote-user"] . "'";
		else $val = "  : ct-remote-user réel = '" . $request->headers->get("ct-remote-user") . "'";

		//--> Construction de la barre
		$barre = "<div style='background: #666; color: white; width: 100%; text-align: left; padding: 0.1em;'><span style='background: #080; text-align: center; padding-left: 0.5em; padding-right: 0.5em;'><a style='color: white; text-decoration: none;' target='_blank' href='" . $this->router->generate('_simulrsa_homepage') . "'>SimulRSA</a></span>$val</div>";

		//--> Injection du code de la réponse dans le haut de la page
		$event->setResponse( new Response( str_replace('<body>', '<body> ' . $barre, $response->getContent()) ) );

	}

}
