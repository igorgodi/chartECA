<?php

// TODO : comment
namespace AppBundle\EventListener;

use Psr\Log\LoggerInterface;

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
	public function stop()
	{
		$this->logger->critical("Inerruption suite erreur en console");
		exit (-1);

	}
}
?>
