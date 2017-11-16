<?php
/*
 *   Commande cronée chargée de traiter les taches asynchrones
 *	Lancement en environnement de developpement : /chemin_charteca/bin/console app:cron
 *	Lancement en environnement de production : /chemin_charteca/bin/console app:cron --env=prod
 *
 *   Note : toute exception non capturée interrompt le script et envoi une erreur critical par interception de l'evenement
 *  	ERROR de la console : voir le gestionnaire d'évenement \AppBundle\EventListener\StopConsoleErreur
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

namespace AppBundle\Command;

use AppBundle\Entity\Moderateur;
use AppBundle\Entity\User;

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
	//private $output = null;

	/** client SOAP du webservice ECA */
	private $wsEca = null;

	/** Gestionnaire d'entitées */
	private $em;

	/** Accès en lecture à l'annuaire LDAP */
	private $ldapReader;

	/** Journal des actions sur les comptes utilisateurs */
	private $journalActions;

	/** Service de gestio comptes utilisateurs */
	private $gestUtils;

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
		//$this->output = $output;

		//--> Objet d'accès au logger : détails https://www.remipoignon.fr/10-symfony-2-configuration-des-logs-monolog
		$this->logger = $this->getContainer()->get('logger');

		//--> Chargement du service d'accès à ECA
		$this->wsEca = $this->getContainer()->get('app.webservice_eca');

		//--> Récupération du service de lecture LDAP
		$this->ldapReader = $this->getContainer()->get('app.reader_ldap');
		
		//--> Récupération du gestionnaire d'entitées
		$this->em = $this->getContainer()->get('doctrine')->getManager();

		//--> Récupération du gestionnaire d'entitées
		$this->journalActions = $this->getContainer()->get('app.journal_actions');

		//--> Récupération du service de gestion des utilisateurs
		$this->gestUtils = $this->getContainer()->get('app.gestion.utilisateur');

		//--> Création d'un id de session (pas au sens http en tout cas) qui permet de retrouver le point d'entrée dans les logs monolog
		$this->logger->info("AppCronCommand::execute(...) : Lancement du processus croné");

		//--> Tache 1 : Vérifier les utilisateurs ECA dans LDAP (AttributApplicationLocale à ECA|UTILISATEUR) et synchroniser la base des utilisateurs ChartECA
		// TODO : la tache 1 est à terminer : mail de notification à chaque utilisateur ???!!!!!!! et correction des incohérences
		$this->maintenanceUtilisateursLdap();

		//--> Tache 2 : Vérifier les utilisateurs ECA dans owncloud (via le webservice dédié) et synchroniser la base des utilisateurs ChartECA
		$this->logger->notice("AppCronCommand::execute(...) : TODO tache 2");
		// TODO : $this->maintenanceUtilisateursEca();

		//--> Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP (AttributApplicationLocale à CHARTECA|MODERATEUR) et synchroniser la base des modérateurs ChartECA
		$this->synchroModerateurs();

		//--> Tache 4 : Vérifier les demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		$this->logger->notice("AppCronCommand::execute(...) : TODO tache 4");
		// TODO : $this->traitementAugmentationQuotas();

		//--> Tache 5 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		$this->logger->notice("AppCronCommand::execute(...) : TODO tache 5");
		// TODO : $this->traitementFinAugmentationQuotas();

		//--> Tache 6 : Traiter les demandes de désactivation des comptes ECA arrivées a échénace
		$this->logger->notice("AppCronCommand::execute(...) : TODO tache 6");
		// TODO : $this->traitementDemandesDesactivationEca();

		//--> Tache 7 : Traiter les oublis de revalidation de la charte : les flags ECA|UTILISATEUR sont supprimés pour le sutilisateurs lorsque la date actuelle est supérieure à la dateMaxiRevalidation de la table User
		$this->logger->notice("AppCronCommand::execute(...) : TODO tache 7");
		// TODO : $this->traitementDemandesDesactivationEca();
		// TODO : ATTENTION : uniquement en ldap DEV !!!!

	}

	
	/**
	 * Tache 1 : Récupérer la liste des utilisateurs de ECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA 
	 *		--> ajout des non inscrits dans ChartECA avec obligation de revalider la charte sous 15 jours
	 * 		--> Correction d'eventuelles incohérences sur les comptes utilisateurs :
	 *			+ etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null
	 **/
	private function maintenanceUtilisateursLdap()
	{
		//--> On journalise
		$this->logger->info("AppCronCommand::maintenanceUtilisateursLdap()(...) : TACHE 1 : Maintenance des utilisateurs ECA dans ldap : ajout des non enregistrés dans ChartECA");

		//--> On recueille toutes les exceptions
		try
		{
			//--> On interroge l'annuraire LDAP pour connaitre la liste des modérateurs inscrits	
			$listeRecordsLdap = $this->ldapReader->getRequest("(AttributApplicationLocale=ECA|UTILISATEUR|*)");
			$this->logger->info("AppCronCommand::maintenanceUtilisateursLdap()(...) : Trouvé " . count($listeRecordsLdap) . " utilisateurs avec requête '(AttributApplicationLocale=ECA|UTILISATEUR|*)'");

			//--> On passe en revue tout les enregistrements et on ajoute en base de données si besoin avec un délai de 15j pour revalider la charte
			for ($x=0 ; $x<count($listeRecordsLdap) ; $x++) 
			{
				// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
				$user = $this->em->getRepository('AppBundle:User')->findOneByUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
				if (!$user)
				{
					// Création du nouvel utilisateur de base
					$this->logger->info("Création de l'utilisateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
					$user = new user();
					$user->setUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
					$user->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
					$this->em->persist($user);
					$this->em->flush();
					// On force la revalidation de la charte dans un délai de 15 jours avant de bloquer l'accès
					$this->gestUtils->etatCompteRevalisationCharte($user, 15);
					// Journaliser
					$this->journalActions->enregistrer($user->getUsername(), "Utilisateur créé automatiquement (cron) dans ChartECA en attente de revalidation avant 15j");
					// TODO : notification à chaque utilisateur ici : voir process???
				}
			}

			//--> TODO : Corriger une fiche utilisateur si incohérence etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null


		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->logger->critical("AppCronCommand::maintenanceUtilisateursLdap() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->logger->debug("AppCronCommand::maintenanceUtilisateursLdap() : " . $e);
		}
	
	}
	
	// TODO tache 2

	/**
	 * Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA
	 **/
	private function synchroModerateurs()
	{
		//--> On journalise
		$this->logger->info("AppCronCommand::synchroModerateurs()(...) : TACHE 3 : Synchronisation de la base des modérateurs ChartECA");

		//--> On recueille toutes les exceptions
		try
		{
			//--> On interroge l'annuraire LDAP pour connaitre la liste des modérateurs inscrits	
			$listeRecordsLdap = $this->ldapReader->getRequest("(AttributApplicationLocale=CHARTECA|MODERATEUR|*)");
			$this->logger->info("AppCronCommand::synchroModerateurs()(...) : Trouvé " . count($listeRecordsLdap) . " modérateurs avec requête '(AttributApplicationLocale=CHARTECA|MODERATEUR|*)'");

			//--> On va ajouter dans la base des modérateurs les enregistrements inexistant et modifier mail sur les existants
			$modUid = array();
			// On passe en revue tout les enregistrements
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
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->logger->critical("AppCronCommand::synchroModerateurs() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->logger->debug("AppCronCommand::synchroModerateurs() : " . $e);
		}
	}


	// TODO taches 4 à 6

}
