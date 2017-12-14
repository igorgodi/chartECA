<?php
/*
 *   Commande cronée chargée vider la base de données afin de pouvoir réaliser la recette
 *	Lancement en environnement de developpement : /chemin_charteca/bin/console app:recette:cleanbase
 *	Lancement en environnement de pré-production : /chemin_charteca/bin/console app:recette:cleanbase --env=preprod
 *	Lancement en environnement de production : /chemin_charteca/bin/console app:recette:cleanbase --env=prod
 *
 *   Notes sur l'écouteur d'évenement 'src/AppBundle/EventListener/StopConsoleErreur.php' : 
 *	- Toute exception non capturée interrompt le script et envoi une erreur critical par interception de l'evenement ERROR de la console.
 *	- Le lancement de ce script en fonction de l'environnement et de la machine est sécurisé afin de ne jamais executer ce script en 'env=prod'
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

namespace RecetteBundle\Command;

use AppBundle\Entity\Moderateur;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande app:recette:cleanbase :
 */
class AppRecetteCleanbaseCommand extends ContainerAwareCommand
{
	//--> Permet de verouiller le process : on ne peut le lancer qu'un seul à la fois
	use LockableTrait;

	/**
	 * Configuration de la commande 
	 */
	protected function configure()
	{
		$this
		    ->setName('app:recette:cleanbase')
		    ->setDescription('Tache permettant de vider la base de donnée en environnement de dev ou préprod et de créer un modérateur fictif (toto@ac-reims.fr) afin de pouvoir réaliser la recette');
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
		$this->getContainer()->get('logger')->info("AppRecetteCleanbaseCommand::execute(...) : Lancement du nettoyage de la base pour la recette");

		// On recueille toutes les exceptions
		try
		{
			//--> Nettoyage des bases de données
			$this->getContainer()->get('logger')->info("AppRecetteCleanbaseCommand::execute(...) : Nettoyage des tables Log, Moderateur, SpoolTache et User");
			// C'est assez bizzare comme démarche mais ça marche bien (https://openclassrooms.com/forum/sujet/symfony-vider-tout-une-table-via-le-controller)
			$connection = $this->getContainer()->get('doctrine')->getConnection();
			$platform   = $connection->getDatabasePlatform();
  			$connection->executeUpdate($platform->getTruncateTableSQL('Log', true));
 			$connection->executeUpdate($platform->getTruncateTableSQL('Moderateur', true));
 			$connection->executeUpdate($platform->getTruncateTableSQL('SpoolTache', true));
 			$connection->executeUpdate($platform->getTruncateTableSQL('User', true));

			//--> Inscrire un modérateur fictif pour activer la boucle de mailing
			$this->getContainer()->get('logger')->info("AppRecetteCleanbaseCommand::execute(...) : Inscription d'un modérateur");
			$moderateur = new Moderateur();
			$moderateur->setUsername("toto");
			$moderateur->setEmail("toto@ac-reims.fr");
			// Persister en db
			$em = $this->getContainer()->get('doctrine')->getManager();
			$em->persist($moderateur);
			$em->flush();
		}
		catch (\Exception $e)
		{
			// Journalise l'erreur
			// Message bref
			$this->getContainer()->get('logger')->critical("AppRecetteCleanbaseCommand::execute() : \Exception() : " . $e->getMessage());
			// Les détails
			$this->getContainer()->get('logger')->debug("AppRecetteCleanbaseCommand::execute() : " . $e);
		}
	}

	
	/*private function synchroUtilisateursLdapChartEca()
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
					$user = new User();
					$user->setUsername($listeRecordsLdap[$x]->getAttribute('uid')[0]);
					$user->setEmail($listeRecordsLdap[$x]->getAttribute('mail')[0]);
					$user->setCn($listeRecordsLdap[$x]->getAttribute('cn')[0]);
					// Convertir le champ FrEduRne en liste de fonctions et établissements si il existe
					$tab = $this->getContainer()->get('app.reader_ldap')->decompFreEduRne($listeRecordsLdap[$x]->getAttribute("FrEduRne"));
					$user->setFonctions($tab["fcts"]);
					$user->setEtablissements($tab["rne"]);
					// Persister en db
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
				// Si on ne le trouve pas dans ldap, on nettoie la base
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

			//--> On va synchroniser les modifciations eventuelles dans les fiches
			$this->getContainer()->get('logger')->info("AppCronCommand::synchroUtilisateursLdapChartEca()(...) TACHE 1c : Mise à jour des champs pour les utilisateurs éxistant");
			$users = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findAll();
			foreach ($users as $user)
			{
				// On vérifie qu'on le trouve bien dans ldap
				if (($rec = $this->getContainer()->get('app.reader_ldap')->getUser($user->getUsername())) != null)
				{ 
					// Mettre à jour valeurs
					$user->setEmail($rec->getAttribute('mail')[0]);
					$user->setCn($rec->getAttribute('cn')[0]);
					// Convertir le champ FrEduRne en liste de fonctions et établissements si il existe
					$tab = $this->getContainer()->get('app.reader_ldap')->decompFreEduRne($rec->getAttribute("FrEduRne"));
					$user->setFonctions($tab["fcts"]);
					$user->setEtablissements($tab["rne"]);
					// Persister en DB
					$em = $this->getContainer()->get('doctrine')->getManager();
					$em->persist($user);
					$em->flush();
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
	}*/
	
}
