<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande app:cron :
 * 	Lancée par le cron toutes les heures: /chemin/bin/console app:cron --env=prod
 */
class AppCronCommand extends ContainerAwareCommand
{
	//--> Permet de verouiller le process : on ne peut le lancer qu'un seul à la fois
	use LockableTrait;

	/** Objet logger */
	private $logger = null;

	/** Objet de sortie écran */
	private $output = null;

	/** client SOAP du webservice ECA */
	private $wsEca = null;

	/** Gestionnaire d'entitées */
	private $em;

	/**
	 * Configuration de la commande 
	 */
	protected function configure()
	{
		$this
		    ->setName('app:cron')
		    ->setDescription('Tache de gestion asynchrones à lancer toutes les heures (Maintenance table utilisateurs, synchro modérateurs, gestion des quotas, désactivation des comptes)');
	}

	/**
	 * Execution de la commande 
	 * 
	 * @param $input Entrée de la commande
	 * @param $output Sortie de la commande
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		//--> On prévient les risques de lancement multiples de la commande
		if (!$this->lock()) 
		{
			$output->writeln("La commande est déjà en jour d'exécution");
			return 0;
		}

		//--> Objet d'accès à la console de sortie 
		$this->output = $output;

		//--> Objet d'accès au logger : détails https://www.remipoignon.fr/10-symfony-2-configuration-des-logs-monolog
		$this->logger = $this->getContainer()->get('logger');

		//--> Chargement du service d'accès à ECA
		$this->wsEca = $this->getContainer()->get('app.webservice_eca');

		//--> Récupération du gestionnaire d'entitées
		$this->em = $this->getContainer()->get('doctrine')->getManager();
		
		//--> Création d'un id de session (pas au sens http en tout cas) qui permet de retrouver le point d'entrée dans les logs monolog
		$this->logger->info("AppCronCommand::execute(...) : Lancement du processus croné");

		//--> Tache 1 : Vérifier les utilisateurs ECA dans LDAP (AttributApplicationLocale à ECA|UTILISATEUR) et synchroniser la base des utilisateurs ChartECA
		// TODO : $this->maintenanceUtilisateursLdap();

		//--> Tache 2 : Vérifier les utilisateurs ECA dans owncloud (via le webservice dédié) et synchroniser la base des utilisateurs ChartECA
		// TODO : $this->maintenanceUtilisateursEca();

		//--> Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP (AttributApplicationLocale à CHARTECA|MODERATEUR) et synchroniser la base des modérateurs ChartECA
		$this->synchroModerateurs();

		//--> Tache 4 : Vérifier les demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		// TODO : $this->traitementAugmentationQuotas();

		//--> Tache 5 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		// TODO : $this->traitementFinAugmentationQuotas();

		//--> Tache 6 : Traiter les demandes de désactivation des comptes ECA arrivées a échénace
		// TODO : $this->traitementDemandesDesactivationEca();

	}

	
	// TODO taches 1 et 2

	/**
	 * Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA
	 **/
	private function synchroModerateurs()
	{
		// On journalise
		$this->logger->info("AppCronCommand::synchroModerateurs()(...) : Exec synchronisation de la base des modérateurs ChartECA (tache 3)");
	
	}


	// TODO taches 4 à 6

}
