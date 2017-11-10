<?php

// TODO : comment
namespace AppBundle\EventListener;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Event\ConsoleErrorEvent;

class StopConsoleErreur
{
	// TODO comment
	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	// TODO : comment
	// stop est chargé automatiquement lors de l'évenement console.error (voir app/config/services.yml)
	public function stop(ConsoleErrorEvent $errorEvent, $chaine)
	{
		$command = $errorEvent->getCommand();

    		$this->logger->critical("ERR : " . $command->getName() . " --> $chaine");
		$this->logger->critical("Interruption suite erreur en console");
		exit (-1);

	}
}
?>
