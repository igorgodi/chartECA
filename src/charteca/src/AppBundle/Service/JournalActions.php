<?php

// TODO : comment
namespace AppBundle\Service;

use AppBundle\Entity\Log;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;


class JournalActions
{
	// TODO comment
	private $em;
	private $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
	{
		$this->em = $em;
		$this->logger = $logger;

	}

	// TODO : comment
	public function enregistrer($username, $traitement, $message)
	{
		// On vérifie les erreurs possibles
		if ($username == ""|| $username ==null) { $this->logger->critical("paramètre 'username' vide ou null"); return; }
		if ($traitement == ""|| $traitement ==null) { $this->logger->critical("paramètre 'traitement' vide ou null"); return; }
		if ($message == ""|| $message ==null) { $this->logger->critical("paramètre 'message' vide ou null"); return; }

		// Créer l'entrée dans le journal
		$this->logger->debug("Création d'une entrée dans le journal des actions ($username --> $traitement --> $message))");
		$log = new Log();
		$log->setUsername($username);
		$log->setTraitement($traitement);
		$log->setMessage($message);	
		$this->em->persist($log);
		$this->em->flush();
	}

}
?>
