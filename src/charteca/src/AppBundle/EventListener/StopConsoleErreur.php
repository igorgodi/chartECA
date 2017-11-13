<?php
/*
 *   Ecouteur de l'évenement ConsoleEvents::ERROR chargé d'interrompre le script en console sur détection d'errreur.
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

namespace AppBundle\EventListener;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Classe de l'évement déclaré par implémentation de l'interface EventSubscriberInterface
 */
class StopConsoleErreur implements EventSubscriberInterface
{
	/** Oblet logger */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(LoggerInterface $logger)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
	}

	/**
	 * Méthode permettant de déclarer comment intercepter l'évènment et la priorité associée
	 * voir https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber	
	 *
	 * @param $logger Objet logger
	 */
	public static function getSubscribedEvents()
	{
		return [
			// Interception des erreurs console
			// Voir: https://symfony.com/doc/current/components/console/events.html
			ConsoleEvents::ERROR => ['stop',  -255],
			// Autres interceptions
			// NOTE : il est possible de gérer plusieurs évènements dans un Subscriber
			// Voir: http://api.symfony.com/master/Symfony/Component/HttpKernel/KernelEvents.html
			//KernelEvents::EXCEPTION => 'handleKernelException',
		];
	}

	/**
	 * Méthode réalisée lors de l'interception de l'évènement
	 *
	 * @param $errorEvent Objet décrivant l'erreur
	 * @param $chaine Chaine de caractères retournée par l'erreur
	 */
	public function stop(ConsoleErrorEvent $errorEvent, $chaine)
	{
		$command = $errorEvent->getCommand();

		if ($command != null && $command->getName() == "app:cron")
		{    		
			$this->logger->critical("Interruption suite erreur en console de la commande app:cron");
			exit (-1);
		}
	}
}
?>
