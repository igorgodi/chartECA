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
use Symfony\Component\Console\Event\ConsoleCommandEvent;
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
			// Interception demarrage de la console
			ConsoleEvents::COMMAND => ['start',  4096]
		];
	}

	/**
	 * Méthode réalisée lors de l'interception de l'évènement juste avant d'executer la commande
	 * https://symfony.com/doc/current/components/console/events.html
	 *
	 * @param $event Objet de la console
	 */
	public function start(ConsoleCommandEvent $event)
	{
		//--> Récupérations de différents objets relatifs à l'événement
		$input = $event->getInput();
		$output = $event->getOutput();
		$command = $event->getCommand();

		// Récupération de l'option de lancement --env (si on lance app:cron sans précise, on est en dev)
		$env = $input->getOption('env');
		// Récupération de l'hostname
		$hostname = exec("hostname");

		//--> On teste ici pour notre commande app:cron
		if ($command != null && $command->getName() == "app:cron")
		{    		
			// Tests en fonction des environnements dev et pré-production
			if ( ($env=="dev" || $env=="preprod") &&
				(   $hostname !="kraken.in.ac-reims.fr"
				 && $hostname !="php56-dev.in.ac-reims.fr"
				 && $hostname !="php56-pp.in.ac-reims.fr"
				 //&& $hostname !="eca2.ac-reims.fr"
				)
			   )
			{
				$message = "Le script app:cron est lancé sur le serveur '$hostname' mais ne peut être executé dans l'environnement '$env'";
				$output->writeln("ERREUR CRITIQUE : " . $message);
				$this->logger->critical($message);
				exit (-2);
			}

			// Tests en fonction des environnements de production
			if  ( $env=="prod" &&
				(   $hostname !="php56.in.ac-reims.fr"
				 //&& $hostname !="eca.ac-reims.fr"
				)
			   )
			{
				$message = "Le script app:cron est lancé sur le serveur '$hostname' mais ne peut être executé dans l'environnement '$env'";
				$output->writeln("ERREUR CRITIQUE : " . $message);
				$this->logger->critical($message);
				exit (-2);
			}
		}

		//--> On teste ici pour notre commande app:recette:cleanbase
		if ($command != null && $command->getName() == "app:recette:cleanbase")
		{    		
			// Tests en fonction des environnements dev et pré-production
			if ( ($env=="dev" || $env=="preprod") &&
				(   $hostname !="kraken.in.ac-reims.fr"
				 && $hostname !="php56-dev.in.ac-reims.fr"
				 && $hostname !="php56-pp.in.ac-reims.fr"
				 //&& $hostname !="eca2.ac-reims.fr"
				)
			   )
			{
				$message = "Le script app:recette:cleanbase est lancé sur le serveur '$hostname' mais ne peut être executé dans l'environnement '$env'";
				$output->writeln("ERREUR CRITIQUE : " . $message);
				$this->logger->critical($message);
				exit (-2);
			}

			// Tests en fonction des environnements de production
			if  ( $env=="prod")
			{
				$message = "Le script app:recette:cleanbase ne peut-être lancé en environnement de production quel que soit le serveur";
				$output->writeln("ERREUR CRITIQUE : " . $message);
				$this->logger->critical($message);
				exit (-2);
			}
		}
	}

	/**
	 * Méthode réalisée lors de l'interception de l'évènement d'erreur
	 * https://symfony.com/doc/current/components/console/events.html
	 *
	 * @param $errorEvent Objet décrivant l'erreur
	 * @param $chaine Chaine de caractères retournée par l'erreur
	 */
	public function stop(ConsoleErrorEvent $errorEvent, $chaine)
	{
		//--> Récupérations de différents objets relatifs à l'événement
		$command = $errorEvent->getCommand();

		//--> On ne teste que pour notre commande app:cron
		if ($command != null && $command->getName() == "app:cron")
		{    		
			$this->logger->critical("Interruption suite erreur en console de la commande app:cron");
			exit (-1);
		}

		//--> On ne teste que pour notre commande app:cron
		if ($command != null && $command->getName() == "app:recette:cleanbase")
		{    		
			$this->logger->critical("Interruption suite erreur en console de la commande app:recette:cleanbase");
			exit (-1);
		}
	}
}
?>
