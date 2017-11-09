<?php

// TODO : licence

namespace AppBundle\Command;

use AppBundle\Entity\Moderateur;

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

	/** Accès en lecture à l'annuaire LDAP */
	private $ldapReader;

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

		//--> Récupération du service de lecture LDAP
		$this->ldapReader = $this->getContainer()->get('app.reader_ldap');
		
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
		//--> On journalise
		$this->logger->info("AppCronCommand::synchroModerateurs()(...) : Exec synchronisation de la base des modérateurs ChartECA (tache 3)");

		//--> On interroge l'annuraire LDAP pour connaitre la liste des modérateurs inscrits	
		$listeRecordsLdap = $this->ldapReader->getRequest("(AttributApplicationLocale=CHARTECA|MODERATEUR|*)");
		$this->logger->info("AppCronCommand::synchroModerateurs()(...) : Trouvé " . count($listeRecordsLdap) . " modérateurs avec requête '(AttributApplicationLocale=CHARTECA|MODERATEUR|*)'");

		//--> On va ajouter dans la base des modérateurs les enregistrements inexistant et modifier mail sur les existants
		$modUid = array();
		for ($x=0 ; $x<count($listeRecordsLdap) ; $x++) 
		{
			// Enregistrer l'uid pour la phase de supressino des modérateurs disparus de l'annuaire
			$modUid[] = $listeRecordsLdap[$x]->getAttribute('uid')[0];

			// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
			$moderateur = $this->em->getRepository('AppBundle:Moderateur')->findOneByUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
			if (!$moderateur)
			{
				$this->logger->info("Création du modérateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
				$moderateur = new Moderateur();
				$moderateur->setUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
				$moderateur->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
				$this->em->persist($moderateur);
				$this->em->flush();
			}
			else 
			{
				$this->logger->info("Mise à jour du modérateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
				$moderateur->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
				$this->em->persist($moderateur);
				$this->em->flush();
			}

		}

		// on va supprimer les modérateurs qui n'existent plus dans ldap
		$moderateurs = $this->em->getRepository('AppBundle:Moderateur')->findAll();
		foreach($moderateurs as $moderateur)
		{
  			// $moderateur est une instance de l'entité Moderateur
  			if (!in_array($moderateur->getUsername(), $modUid))
			{
				$this->logger->info("Supression du modérateur uid='" . $moderateur->getUsername() . "' non présent dans l'annuaire LDAP");
				// Suppression en base de données
				$this->em->remove($moderateur);
				$this->em->flush();
			}
		}
	}


	// TODO taches 4 à 6

}
