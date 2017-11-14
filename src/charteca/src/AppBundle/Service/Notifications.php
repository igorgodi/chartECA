<?php
/*
 *   Service chargé de gérer les notifications de l'application aux utilisateurs et modérateurs
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

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Psr\Log\LoggerInterface;


// TODO : réaliser un service (ou 2 imbriqués, un réutilisable et l'autre pas) ???
// TODO : mémo pour les autres actions du service
//		->attach(Swift_Attachment::fromPath('my-document.pdf'))


/**
 * Objet de gestion des appels SOAP
 */
class Notifications
{
	/** Oblet logger */
	private $logger;

	/** Gestionnaire d'entité */
	private $em;

	/** Service de journalisation des actions */
	private $journalActions;

	/** Service de mails */
	private $mailer;

	/** Service de template */
	private $templating;

	/** Emetteur de la notification */
	private $notificationFrom;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(LoggerInterface $logger, EntityManagerInterface $em, JournalActions $journalActions, \Swift_Mailer $mailer, EngineInterface $templating, $notificationFrom)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->em = $em;
		$this->journalActions = $journalActions;
		$this->mailer = $mailer;
		$this->templating = $templating;
		$this->notificationFrom = $notificationFrom;
	}

	/**
	 * Envoyer une notification de demande d'uverture de compte aux modérateurs
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 *
	 * @return Réponse de la méthode lancée sur le serveur
	 */
	// TODO : créer un service app.mail_template_html à fort taux de réutilisabilité qui sera appelé par celui ci
	public function demandeOuvertureCompteEca($user) 
	{
		//--> Extraire la liste de mail des modérateurs
		$listeModerateurs = array();
		$moderateurs = $this->em->getRepository('AppBundle:Moderateur')->findAll();
		foreach($moderateurs as $moderateur) $listeModerateurs[] = $moderateur->getEmail();
		// Si pas de modérateurs dans ce cas, message dans journal user et error dans log appli.
		if (count($listeModerateurs) == 0) 
		{
			$this->journalActions->enregistrer($user->getUsername(), "Pas d'émail de notification envoyé aux modérateurs car aucun de définit !!!");
			$this->logger->error("Pas de modérateur définit, envoi d'email impossible");
			// Fin
			return;
		}

		//--> Envoi du message
		$mail = (new \Swift_Message())
		  ->setContentType("text/html")
		  ->setSubject("Une demande d'accès à ECA est en attente de modération")
		  ->setFrom($this->notificationFrom)
		  ->setTo($listeModerateurs)
		  ->setBody($this->templating->render('AppBundle:Notifications:demandeOuvertureCompteEca.html.twig', ["user"=> $user]));
		// Envoi du mail avec le service mail
		$this->mailer->send($mail);
		// inscription dans le journal des actions
		$this->journalActions->enregistrer($user->getUsername(), "Email envoyé aux modérateurs (" . implode (" ; ", $listeModerateurs) . ")");
	}
}
