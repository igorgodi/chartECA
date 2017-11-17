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

		//--> On journalise l'entrée
		$this->getContainer()->get('logger')->info("AppCronCommand::execute(...) : Lancement du processus croné");

		//--> Tache 1 : Vérifier les utilisateurs ECA dans LDAP (AttributApplicationLocale à ECA|UTILISATEUR) et synchroniser la base des utilisateurs ChartECA
		// TODO : la tache 1 est à terminer : mail de notification à chaque utilisateur ???!!!!!!!
		$this->maintenanceUtilisateursLdap();

		//--> Tache 2 : Maintenance des bases ChartECA, correction d'eventuelles incohérences
		$this->maintenanceBasesChartECA();

		//--> Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP (AttributApplicationLocale à CHARTECA|MODERATEUR) et synchroniser la base des modérateurs ChartECA
		$this->synchroModerateurs();

		//--> Tache 4 : Vérifier les demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		$this->traitementAugmentationQuotas();

		//--> Tache 5 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		// TODO : $this->traitementFinAugmentationQuotas();

		//--> Tache 6 : Traiter les demandes de désactivation des comptes ECA arrivées a échénace
		// TODO : $this->traitementDemandesDesactivationEca();

		//--> Tache 7 : Traiter les oublis de revalidation de la charte : les flags ECA|UTILISATEUR sont supprimés pour le sutilisateurs lorsque la date actuelle est supérieure à la dateMaxiRevalidation de la table User
		// TODO : $this->traitementDemandesDesactivationEca();
		// TODO : ATTENTION : uniquement en ldap DEV  ou LdapWriter désactivé !!!!

	}

	
	/**
	 * Tache 1 : Synchro entre l'annuaire LDAP et la base interne ChartECA 
	 *		--> ajout des non inscrits dans ChartECA avec obligation de revalider la charte sous 15 jours
	 *		--> TODO suppression de ChartECA des utilisateurs disparus de ldap
	 **/
	private function maintenanceUtilisateursLdap()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceUtilisateursLdap()(...) TACHE 1 : Maintenance des utilisateurs ECA dans ldap");

		// On recueille toutes les exceptions
		try
		{
			//--> Ajout des utilisateurs depuis LDAP
			$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceUtilisateursLdap()(...) TACHE 1a : Ajout des utilisateurs trouvés dans ldap");
			// On interroge l'annuaire LDAP pour connaitre la liste des utilisateurs ECA
			$listeRecordsLdap = $this->getContainer()->get('app.reader_ldap')->getRequest("(AttributApplicationLocale=ECA|UTILISATEUR|*)");
			$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceUtilisateursLdap()(...) : Trouvé " . count($listeRecordsLdap) . " utilisateurs avec requête '(AttributApplicationLocale=ECA|UTILISATEUR|*)'");

			// On passe en revue tout les enregistrements et on ajoute en base de données si besoin avec un délai de 15j pour revalider la charte
			for ($x=0 ; $x<count($listeRecordsLdap) ; $x++) 
			{
				// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
				$user = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneByUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
				if (!$user)
				{
					// Création du nouvel utilisateur de base
					$this->getContainer()->get('logger')->info("Création de l'utilisateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
					$user = new user();
					$user->setUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
					$user->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
					$em = $this->getContainer()->get('doctrine')->getManager();
					$em->persist($user);
					$em->flush();
					// On force la revalidation de la charte dans un délai de 15 jours avant de bloquer l'accès
					$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user, 15);
					// Journaliser
					$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "Utilisateur créé automatiquement (cron) dans ChartECA en attente de revalidation avant 15j");
					// TODO : notification à chaque utilisateur ici : voir process???
				}
			}

			//--> TODO : Suppression des comptes utilisateurs (et table journal des action) disparus dans ldap
			$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceUtilisateursLdap()(...) TACHE 1b : Suppression des utilisateurs disparus dans ldap");




		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::maintenanceUtilisateursLdap() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::maintenanceUtilisateursLdap() : " . $e);
		}
	}
	
	/**
	 * Tache 2 : Maintenance des bases ChartECA, correction d'eventuelles incohérences : 
	 *	--> sur les comptes utilisateurs :
	 *		+ etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null
	 *		+ TODO : etat_compte = User::ETAT_COMPTE_INACTIF et flag ECA|UTILISATEUR| trouvé dans ldap
	 *		+ TODO : etat_compte = User::ETAT_COMPTE_ATTENTE_VALIDATION et flag ECA|UTILISATEUR| trouvé dans ldap
	 **/
	private function maintenanceBasesChartECA()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceBasesChartECA()(...) TACHE 2 : Maintenance des bases ChartECA");

		// On recueille toutes les exceptions
		try
		{
			//--> Corriger une fiche utilisateur si incohérence etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null
			$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceBasesChartECA()(...) TACHE 2a : Vérifier incohérence etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null");
			// Lister les utilisateurs en erreur
			$listeDefauts = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findErreurDateRevalidationCharte();
			foreach ($listeDefauts as $user) 
			{
				// Replacer eu délai de 15j
				$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user, 15);
				// Journaliser
				$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "Utilisateur en erreur de date de revalidation : mise en place d'un délai de 15j");
				// Envoyer une notification de revalidation de charte
				$this->getContainer()->get('app.notification.mail')->revalidationCharte($user, 15);
			}

			//--> TODO : etat_compte = User::ETAT_COMPTE_INACTIF et flag ECA|UTILISATEUR| trouvé dans ldap


			//--> TODO : etat_compte = User::ETAT_COMPTE_ATTENTE_VALIDATION et flag ECA|UTILISATEUR| trouvé dans ldap


		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::maintenanceUtilisateursLdap() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::maintenanceUtilisateursLdap() : " . $e);
		}
	}

	/**
	 * Tache 3 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA
	 **/
	private function synchroModerateurs()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::synchroModerateurs()(...) TACHE 3 : Synchronisation de la base des modérateurs ChartECA");

		// On recueille toutes les exceptions
		try
		{
			//--> On interroge l'annuraire LDAP pour connaitre la liste des modérateurs inscrits	
			$listeRecordsLdap = $this->getContainer()->get('app.reader_ldap')->getRequest("(AttributApplicationLocale=CHARTECA|MODERATEUR|*)");
			$this->getContainer()->get('logger')->info("AppCronCommand::synchroModerateurs()(...) : Trouvé " . count($listeRecordsLdap) . " modérateurs avec requête '(AttributApplicationLocale=CHARTECA|MODERATEUR|*)'");

			//--> On va ajouter dans la base des modérateurs les enregistrements inexistant et modifier mail sur les existants
			$modUid = array();
			// On passe en revue tout les enregistrements
			for ($x=0 ; $x<count($listeRecordsLdap) ; $x++) 
			{
				// Enregistrer l'uid pour la phase de supressino des modérateurs disparus de l'annuaire
				$modUid[] = $listeRecordsLdap[$x]->getAttribute('uid')[0];

				// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
				$moderateur = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Moderateur')->findOneByUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
				if (!$moderateur)
				{
					$this->getContainer()->get('logger')->info("Création du modérateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
					$moderateur = new Moderateur();
					$moderateur->setUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
					$moderateur->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
					$em = $this->getContainer()->get('doctrine')->getManager();
					$em->persist($moderateur);
					$em->flush();
				}
				else 
				{
					$this->getContainer()->get('logger')->info("Mise à jour du modérateur '" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' --> '" . $listeRecordsLdap[$x]->getAttribute('mail')[0] ."'");
					$moderateur->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
					$em = $this->getContainer()->get('doctrine')->getManager();
					$em->persist($moderateur);
					$em->flush();
				}

			}

			// on va supprimer les modérateurs qui n'existent plus dans ldap
			$moderateurs = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Moderateur')->findAll();
			foreach($moderateurs as $moderateur)
			{
	  			// $moderateur est une instance de l'entité Moderateur
	  			if (!in_array($moderateur->getUsername(), $modUid))
				{
					$this->getContainer()->get('logger')->info("Supression du modérateur uid='" . $moderateur->getUsername() . "' non présent dans l'annuaire LDAP");
					// Suppression en base de données
					$this->getContainer()->get('doctrine')->remove($moderateur);
					$this->getContainer()->get('doctrine')->flush();
				}
			}
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::synchroModerateurs() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::synchroModerateurs() : " . $e);
		}
	}

	/**
	 * Tache 4 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA
	 **/
	private function traitementAugmentationQuotas()
	{
		//--> On journalise
		/*$this->getContainer()->get('logger')->info("AppCronCommand::traitementAugmentationQuotas()(...) TACHE 4 : Vérification des demandes d'augmentation de quota à appliquer dans ECA");

		// On recueille toutes les exceptions
		try
		{
			// Vérification que le webservice répond
			$ret = $this->getContainer()->get('app.webservice_eca')->appel("hello", array());
			print "retour SOAP = " . $ret["ok"] . "\n";
			// TODO : devel ici 





		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::traitementAugmentationQuotas() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::traitementAugmentationQuotas() : " . $e);
		}*/

	}

	// TODO taches 5 à 7
}
