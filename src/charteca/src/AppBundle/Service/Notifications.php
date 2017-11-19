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

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Synfony\Component\Form\Exception\InvalidArgumentException;

use Psr\Log\LoggerInterface;


// TODO : créer un service app.mail_template_html à fort taux de réutilisabilité qui sera appelé par celui ci ????
// TODO : mémo pour les autres actions du service
//		->attach(Swift_Attachment::fromPath('my-document.pdf'))


/**
 * Objet de gestion des notifications de l'application
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
	 * @param $journalActions Service de journalisation des actions utilisateurs
	 * @param $mail Service d'envoi de mails
	 * @param $templating Service de rendu des templates
	 * @param $notificationFrom Adresse d'emission de la notification
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
	 * Envoyer une notification de demande d'ouverture de compte aux modérateurs
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function demandeOuvertureCompteEca($user) 
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("Notifications::demandeOuvertureCompteEca : L'objet \$user transmis n'est pas du type de l'entité 'User'");

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

	/**
	 * Envoyer une notification à l'utilisateur comme quoi sa demande d'utilisation a bien été acceptée
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function demandeOuvertureCompteEcaAcceptee($user)
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("Notifications::demandeOuvertureCompteEca : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> Envoi du message
		$mail = (new \Swift_Message())
		  ->setContentType("text/html")
		  ->setSubject("Votre demande d'accès à ECA a été acceptée")
		  ->setFrom($this->notificationFrom)
		  ->setTo($user->getEmail())
		  ->setBody($this->templating->render('AppBundle:Notifications:demandeOuvertureCompteEcaAcceptee.html.twig', ["user"=> $user]));
		// Envoi du mail avec le service mail
		$this->mailer->send($mail);
		// inscription dans le journal des actions
		$this->journalActions->enregistrer($user->getUsername(), "Email d'acceptation utilisation ECA envoyé à l'utilisateur");
	}

	/**
	 * Envoyer une notification à l'utilisateur comme quoi sa demande d'utilisation a été refusée
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 * @param $motif Motif de refus
	 */
	public function demandeOuvertureCompteEcaRefusee($user, $motif)
	{
		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("Notifications::demandeOuvertureCompteEcaRefusee : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> Envoi du message
		$mail = (new \Swift_Message())
		  ->setContentType("text/html")
		  ->setSubject("Votre demande d'accès à ECA a été refusée")
		  ->setFrom($this->notificationFrom)
		  ->setTo($user->getEmail())
		  ->setBody($this->templating->render('AppBundle:Notifications:demandeOuvertureCompteEcaRefusee.html.twig', ["user" => $user, "motif_refus"=> $motif]));
		// Envoi du mail avec le service mail
		$this->mailer->send($mail);
		// inscription dans le journal des actions
		$this->journalActions->enregistrer($user->getUsername(), "Email de refus d'utilisation ECA envoyé à l'utilisateur");
	}

	/**
	 * Envoyer une notification à l'utilisateur comme quoi il a $delai jours pour revalider la charte
	 *
	 * @param $user Objet de type User représentatif de l'utilisateur réalisant la demande
	 */
	public function revalidationCharte($user)
	{
		// TODO Devel : Désactivé jusqu'a régler le pb des env de préprod ou preprod = prod moins les mails redirigés vers un user.
		$this->logger->notice("Notifications::revalidationCharte() : L'envoi de mail de notification revalidation charte est désactivé en mode devel voir code pour commentaires");
		return;

		//--> Vérification des arguments transmis
		if ( !($user instanceof User) ) throw new InvalidArgumentException("Notifications::revalidationCharte() : L'objet \$user transmis n'est pas du type de l'entité 'User'");

		//--> Envoi du message
		$mail = (new \Swift_Message())
		  ->setContentType("text/html")
		  ->setSubject("Vous devez revalider la charte d'accès à ECA")
		  ->setFrom($this->notificationFrom)
		  ->setTo($user->getEmail())
		  ->setBody($this->templating->render('AppBundle:Notifications:revalidationCharte.html.twig', ["user" => $user ]));
		// Envoi du mail avec le service mail
		$this->mailer->send($mail);
		// inscription dans le journal des actions
		$this->journalActions->enregistrer($user->getUsername(), "Email de revalidation charte ECA envoyé à l'utilisateur");
	}


}
