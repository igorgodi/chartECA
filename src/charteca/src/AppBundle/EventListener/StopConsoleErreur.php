<?php

// TODO : comment
namespace AppBundle\EventListener;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StopConsoleErreur implements EventSubscriberInterface
{
	// TODO comment
	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	// voir https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber
	public static function getSubscribedEvents()
	{
		return [
			// Voir: https://symfony.com/doc/current/components/console/events.html
			ConsoleEvents::ERROR => ['stop',  -255],
			// Voir: http://api.symfony.com/master/Symfony/Component/HttpKernel/KernelEvents.html
			//KernelEvents::EXCEPTION => 'handleKernelException',
		];
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

	/**
	* This method checks if the triggered exception is related to the database
	* and then, it checks if the required 'sqlite3' PHP extension is enabled.
	*
	* @param GetResponseForExceptionEvent $event
	*/
	/*public function handleKernelException(GetResponseForExceptionEvent $event)
	{
		$exception = $event->getException();
		// Since any exception thrown during a Twig template rendering is wrapped
		// in a Twig_Error_Runtime, we must get the original exception.
		$previousException = $exception->getPrevious();

		// Driver exception may happen in controller or in twig template rendering
		$isDriverException = ($exception instanceof DriverException || $previousException instanceof DriverException);

		// Check if SQLite is enabled
		if ($isDriverException && $this->isSQLitePlatform() && !extension_loaded('sqlite3')) {
		    $event->setException(new \Exception('PHP extension "sqlite3" must be enabled because, by default, the Symfony Demo application uses SQLite to store its information.'));
		}
	}*/

}
?>
