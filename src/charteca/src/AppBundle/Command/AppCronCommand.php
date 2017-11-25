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
		$this->synchroUtilisateursLdapChartEca();

		//--> Tache 2 : Maintenance des bases ChartECA, correction d'eventuelles incohérences
		$this->maintenanceBasesChartECA();

		//--> Tache 3 : Traiter les demandes de désactivation des comptes ECA arrivées a échéance
		// TODO : dev phase 2
		$this->traitementDemandesDesactivationEca();

		//--> Tache 4 : Traiter les oublis de revalidation de la charte : les flags ECA|UTILISATEUR sont supprimés pour les utilisateurs lorsque la date actuelle est supérieure à la dateMaxiRevalidation de la table User
		$this->traitementDemandesRevalidationEca();

		//--> Tache 5 : Vérifier les demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		// TODO : dev phase 2
		$this->traitementAugmentationQuotas();

		//--> Tache 6 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA (via le webservice dédié)
		// TODO : dev phase 2
		$this->traitementFinAugmentationQuotas();

		//--> Tache 7 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP (AttributApplicationLocale à CHARTECA|MODERATEUR) et synchroniser la base des modérateurs ChartECA
		$this->synchroModerateurs();

		//--> Tache 8 : Vider le spooler de taches : re-publication charte ECA (évite l'envoi en masse de mails en mode web)
		$this->viderSpoolerTaches();

	}

	
	/**
	 * Tache 1 : Synchro entre l'annuaire LDAP et la base interne ChartECA :
	 *		--> ajout des non inscrits dans ChartECA avec obligation de revalider la charte sous 15 jours
	 *		--> suppression de ChartECA des utilisateurs disparus de ldap
	 **/
	private function synchroUtilisateursLdapChartEca()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) TACHE 1 : Maintenance des utilisateurs ECA dans ldap");

		// On recueille toutes les exceptions
		try
		{
			//--> Ajout des utilisateurs depuis LDAP
			$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) TACHE 1a : Ajout des utilisateurs trouvés dans ldap si inexistants");
			// On interroge l'annuaire LDAP pour connaitre la liste des utilisateurs ECA
			$listeRecordsLdap = $this->getContainer()->get('app.reader_ldap')->getRequest("(AttributApplicationLocale=ECA|UTILISATEUR|*)");
			$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) : Trouvé " . count($listeRecordsLdap) . " utilisateurs avec requête '(AttributApplicationLocale=ECA|UTILISATEUR|*)'");

			// On passe en revue tout les enregistrements et on ajoute en base de données si besoin avec un délai pour revalider la charte
			for ($x=0 ; $x<count($listeRecordsLdap) ; $x++) 
			{
				// Gestion des erreurs de remontés de ldap
				if ($listeRecordsLdap[$x]->getAttribute('mail')[0] == null)
				{
					$this->getContainer()->get('logger')->error("Le mail de l'utilisateur ldap uid='" . $listeRecordsLdap[$x]->getAttribute('uid')[0] . "' est null, on ne peut l'utiliser (pb notifications)");
					continue;
				}
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
					$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user);
					// Journaliser
					$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "(CRON) Utilisateur créé automatiquement dans ChartECA en attente de revalidation");
					// Envoyer une notification de revalidation de charte
					$this->getContainer()->get('app.notification.mail')->revalidationCharte($user);
					// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
				}
			}

			//--> Suppression des comptes utilisateurs (et table journal des action) disparus dans ldap
			$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) TACHE 1b : Suppression des utilisateurs disparus dans l'annuaire ldap");
			// On charge la liste des utilisateurs de charteca
			$users = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findAll();
			foreach ($users as $user)
			{
				// On vérifie qu'on le trouve bien dans ldap
				if ($this->getContainer()->get('app.reader_ldap')->getUser($user->getUsername()) == null)
				{ 
					// Supprimer l'utilisateur en trop
					$em = $this->getContainer()->get('doctrine')->getManager();
					$em->remove($user);
					$em->flush();
					// Supprimer dans le journal des actions
					$this->getContainer()->get('doctrine')->getRepository('AppBundle:Log')->deleteLogsUser($user->getUsername());
					// Journaliser dans symfony
					$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) TACHE 1b : l'utilisateur " . $user->getUsername() . " a été supprimé");
				}
			}
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::synchroUtilisateursLdapChartEca() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::synchroUtilisateursLdapChartEca() : " . $e);
		}
	}
	
	/**
	 * Tache 2 : Maintenance des bases ChartECA, correction d'eventuelles incohérences : 
	 *	--> sur les comptes utilisateurs :
	 *		+ etat_compte = User::ETAT_COMPTE_REVALIDATION_CHARTE et date_maxi_revalidation_charte = null
	 *		+ etat_compte = (User::ETAT_COMPTE_INACTIF ou User::ETAT_COMPTE_ATTENTE_VALIDATION) et flag ECA|UTILISATEUR| trouvé dans ldap
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
				// Revalidation
				$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user);
				// Journaliser
				$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "(CRON) Utilisateur en erreur de date de revalidation");
				// Envoyer une notification de revalidation de charte
				$this->getContainer()->get('app.notification.mail')->revalidationCharte($user);
				// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
			}

			//--> Corriger flag ECA si (User::ETAT_COMPTE_INACTIF ou User::ETAT_COMPTE_ATTENTE_VALIDATION) et flag ECA|UTILISATEUR| trouvé dans ldap
			// Cette manipulation ne pose pas de soucis car auparavant en étape 1a, on a intégré les utilisateurs qui n'existait pas dans ChartECA en les passant en mode User::ETAT_COMPTE_REVALIDATION_CHARTE
			$this->getContainer()->get('logger')->info("AppCronCommand::maintenanceBasesChartECA()(...) TACHE 2b : etat_compte = (User::ETAT_COMPTE_INACTIF ou User::ETAT_COMPTE_ATTENTE_VALIDATION) et flag ECA|UTILISATEUR| trouvé dans ldap");
			// Lister les utilisateurs en erreur
			$listeDefauts = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findUsersInactifOuAttente();
			foreach ($listeDefauts as $user) 
			{
				// Vérification présence du flag ECA pour cet utilisateur inactif
				$fiches = $this->getContainer()->get('app.reader_ldap')->getRequest("(&(AttributApplicationLocale=ECA|UTILISATEUR|*)(uid=" . $user->getUsername() . "))");
				if (count($fiches) != 0)
				{
					$ancienEtat = $user->getEtatCompte();
					// Revalidation
					$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user);
					// Journaliser
					$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "(CRON) Utilisateur ayant un accès ECA et connu dans CHARTECA (etatCompte='$ancienEtat') : mise en place d'une ravalidation");
					// Envoyer une notification de revalidation de charte
					$this->getContainer()->get('app.notification.mail')->revalidationCharte($user);
					// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
				}
			}
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::maintenanceBasesChartECA() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::maintenanceBasesChartECA() : " . $e);
		}
	}

	/**
	 * Tache 3 : Traiter les demandes de désactivation des comptes ECA arrivées a échéance
	 **/
	private function traitementDemandesDesactivationEca()
	{
		//--> On journalise
		/*$this->getContainer()->get('logger')->info("AppCronCommand::traitementDemandesDesactivationEca()(...) TACHE 3 : Traiter les demandes de désactivation des comptes ECA arrivées a échéance");

		// On recueille toutes les exceptions
		try
		{
			//TODO




		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::traitementDemandesDesactivationEca() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::traitementDemandesDesactivationEca() : " . $e);
		}*/

	}

	/**
	 * Tache 4 : Traiter les oublis de revalidation de la charte : les flags ECA|UTILISATEUR sont supprimés pour les utilisateurs lorsque la date actuelle est supérieure à la dateMaxiRevalidation de la table User
	 **/
	private function traitementDemandesRevalidationEca()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::traitementDemandesRevalidationEca()(...) TACHE 4 : Traiter les oublis de revalidation de la charte : les flags ECA|UTILISATEUR sont supprimés pour les utilisateurs lorsque la date actuelle est supérieure à la dateMaxiRevalidation de la table User");

		// On recueille toutes les exceptions
		try
		{
			// Lister les utilisateurs en attente de revalidation dépassée
			$listeDepasse = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findDateRevalidationChartePassee();
			foreach ($listeDepasse as $user) 
			{
				// On va lui supprimer son flag d'accès ECA si il existe
				$fiches = $this->getContainer()->get('app.reader_ldap')->getRequest("(&(AttributApplicationLocale=ECA|UTILISATEUR|*)(uid=" . $user->getUsername() . "))");
				if (count($fiches) != 0)
				{
					// On lui supprime l'attribut d'accès
					$this->getContainer()->get('app.writer_ldap')->supprEntreeAttributApplicationLocale($user->getUsername(), "ECA", "UTILISATEUR", "", "");
					// Pas de journal des action une ligne par jour sinon !!!! 
					$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "(CRON) Utilisateur n'ayant pas revalidé la charte : suppression accès ECA");
					// Journaliser
					$this->getContainer()->get('logger')->info("AppCronCommand::traitementDemandesRevalidationEca()(...) TACHE 4 : l'utilisateur '" . $user->getUsername() . "' n'a pas revalidé sa charte, on lui supprime son flag ECA|UTILISATEUR|");
					// Notifier que le compte est bloqué car la charte n'a pas été revalidée à temps
					$this->getContainer()->get('app.notification.mail')->revalidationCharteNonRealisee($user);
					// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
				}
			}
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::traitementDemandesRevalidationEca() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::traitementDemandesRevalidationEca() : " . $e);
		}

	}

	/**
	 * Tache 5 : Vérifier les demandes d'augmentation de quota et appliquer dans ECA
	 **/
	private function traitementAugmentationQuotas()
	{
		//--> On journalise
		/*$this->getContainer()->get('logger')->info("AppCronCommand::traitementAugmentationQuotas()(...) TACHE 5 : Vérification des demandes d'augmentation de quota à appliquer dans ECA");

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

	/**
	 * Tache 6 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA
	 **/
	private function traitementFinAugmentationQuotas()
	{
		//--> On journalise
		/*$this->getContainer()->get('logger')->info("AppCronCommand::traitementFinAugmentationQuotas()(...) TACHE 6 : Vérifier la fin des demandes d'augmentation de quota et appliquer dans ECA");

		// On recueille toutes les exceptions
		try
		{
			//TODO




		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::traitementFinAugmentationQuotas() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::traitementFinAugmentationQuotas() : " . $e);
		}*/

	}

	/**
	 * Tache 7 : Récupérer la liste des modérateurs ChartECA dans l'annuaire LDAP et synchroniser la base des modérateurs ChartECA
	 **/
	private function synchroModerateurs()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::synchroModerateurs()(...) TACHE 7 : Synchronisation de la base des modérateurs ChartECA");

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
	 * Tache 8 : Vider le spooler de taches de publication charte ECA (évite l'envoi en masse en mode web)
	 **/
	private function viderSpoolerTaches()
	{
		//--> On journalise
		$this->getContainer()->get('logger')->info("AppCronCommand::viderSpoolerTaches()(...) Tache 8 : Vider le spooler de taches");

		// On recueille toutes les exceptions
		try
		{
			//--> Dépilage des taches pour les utilisateurs actifs
			while ($tache = $this->getContainer()->get('app.spooler.taches')->pull("publicationCharteActifs"))
			{
				// Recherche de l'utilisateur concerné
				$user = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneById($tache->getUserId());
				if ($user == null) $this->getContainer()->get('logger')->notice("Utilisateur id=" . $tache->getUserId() . " disparu de l'annuaire");
				// Réalisation de l'action associée si trouvé
				else
				{
					// on va vérifier que le status est encore d'actualité (cas d'une modif dans un des process précédant : par exemple correctif de la tache 2)
					if (!$user->isEtatActif()) $this->getContainer()->get('logger')->notice("Utilisateur username=" . $user->getUsername() . " n'est plus actif (sans doute corrigé par une tache précédente de ce script)");
					else
					{
						// Revalidation
						$this->getContainer()->get('app.gestion.utilisateur')->etatCompteRevalidationCharte($user);
						// Journaliser
						$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "Publication d'une nouvelle charte : status devient revalidation_charte");
						// Envoyer une notification de revalidation de charte
						$this->getContainer()->get('app.notification.mail')->revalidationCharte($user);
					}
				}
				// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
			}

			//--> Dépilage des taches pour les utilisateurs en attente de modération
			while ($tache = $this->getContainer()->get('app.spooler.taches')->pull("publicationCharteAttentes"))
			{
				// Recherche de l'utilisateur concerné
				$user = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneById($tache->getUserId());
				if ($user == null) $this->getContainer()->get('logger')->notice("Utilisateur id=" . $tache->getUserId() . " disparu de l'annuaire");
				// Réalisation de l'action associée si trouvé
				else
				{
					// on va vérifier que le status est encore d'actualité (cas d'une modif dans un des process précédant : par exemple correctif de la tache 2)
					if (!$user->isEtatModeration()) $this->getContainer()->get('logger')->notice("Utilisateur username=" . $user->getUsername() . " n'est plus en attente de modération (sans doute corrigé par une tache précédente de ce script)");
					else
					{
						// Revalidation
						$this->getContainer()->get('app.gestion.utilisateur')->etatCompteInactif($user);
						// Journaliser
						$this->getContainer()->get('app.journal_actions')->enregistrer($user->getUsername(), "Publication d'une nouvelle charte : status devient inactif");
						// Envoyer une notification de revalidation de charte
						$this->getContainer()->get('app.notification.mail')->revalidationCharteParModeration($user);
					}
				}
				// TODO Si besoin (pb anti-spam) : distiller 1 mail par 10ms
			}
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppCronCommand::viderSpoolerTaches() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppCronCommand::viderSpoolerTaches() : " . $e);
		}

	}



}
