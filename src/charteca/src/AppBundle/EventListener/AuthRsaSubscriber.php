<?php
/*
 *  Gestion du firewall Symfony
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

namespace AppBundle\EventListener;

use AcReims\AuthRsaBundle\Events;
use AcReims\AuthRsaBundle\Service\AttributsRsaInterface;
use AcReims\StatsBundle\Service\StatsInterface;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;



/**
 * Classe permettant de transmettre les informations d'authentification
 */
class AuthRsaSubscriber implements EventSubscriberInterface
{
	/** Servie de lecture des attributs RSA */
	private $rsa;

	/** Gestionnaire d'entité */
	private $em;

	/** Service de gestion des logs */
	private $logger;

	/** Pile de requêtes */
	private $requestStack;

	/** Gestionnaire des statistiques */
	private $stats;


	/**
	 * Constructeur
	 *
	 * @param $rsa Objet permettant de traiter les champs RSA
	 * @param $en Gestionnaire d'entités
	 * @param $logger Objet logger
	 * @param $requestStack Objet de pile de requête
	 * @param $stats Gestion des statistiques
	 */
	public function __construct(AttributsRsaInterface $rsa, EntityManagerInterface $em, LoggerInterface $logger, RequestStack $requestStack, StatsInterface $stats)
	{
		// Sauvegarde des objets
		$this->rsa = $rsa;
		$this->em = $em;
		$this->logger = $logger;
		$this->requestStack = $requestStack;
		$this->stats = $stats;
	}

	/**
	 * Méthode chargée de déclarer les événements à écouter
	 */
	public static function getSubscribedEvents()
	{
		return [
		    Events::RSA_AUTH_GET_CREDENTIAL => 'onRsaGetCredential',
		    Events::RSA_AUTH_GET_USER => 'onRsaGetUser',
		];
	}

	/**
 	 * Evènement lancée lors de la méthode getCredentials() dans Security/RsaAuthenticator.php du bundle AcReims\AuthRsaBundle
 	 * 
	 * @param GenericEvent $event
	 */
	public function onRsaGetCredential(GenericEvent $event)
	{
		//--> Vide par défaut
		$username = null;

		//--> Si l'attribut a été relevé dans RSA
		$tabAttribs = $this->rsa->getAttribs();
		if (isset($tabAttribs['ct_remote_user'])) $username = $tabAttribs['ct_remote_user'];
		
		//--> Envoyer la réponse dans les attributs de la requête
		$request = $this->requestStack->getCurrentRequest();
		$request->attributes->set('_ac_reims_auth_rsa_credential', $username);
	}

	/**
 	 * Evènement lancée lors de la méthode getUser() dans Security/RsaAuthenticator.php du bundle AcReims\AuthRsaBundle
 	 * 
	 * @param GenericEvent $event
	 */
	public function onRsaGetUser(GenericEvent $event)
	{
		/** @var string $username */
		$username = $event->getSubject();

		//--> Créer ici l'utilisateur
		$tabAttribs = $this->rsa->getAttribs();
		//--> Création ou correction de l'utilisateur dans l'entité User
		$user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);
		// Si il n'existe pas, on crée la fiche
		if (!$user)
		{
			$this->logger->info("Création de l'utilisateur '$username' non présent dans la table des utilisateurs");
			$user = new User();
			$user->setUsername($username);
			$user->setEtatCompte(User::ETAT_COMPTE_INACTIF);	// Note : déjà fait par défaut dans l'entité
		}
		else $this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");
		// Ajout ou mise à jour des champs et persistance
		$user->setEmail($tabAttribs["ctemail"]);
		$user->setCn($tabAttribs["cn"]);
		// Convertir le champ FrEduRne en liste de fonctions et établissements si il existe
		$tabFredurne = $tabAttribs["fredurne"];
		$tabRne = array();
		$tabFct = array();
		// On décompose entrée par entrée
		for ($j=0 ; $j<count($tabFredurne) ; $j++) 
		{
			$ligne = explode("$", $tabFredurne[$j]);
			if (count($ligne)==8 && $ligne[3]!= '' && array_search($ligne[3], $tabFct, true)===false) $tabFct[] = $ligne[3];
			if (count($ligne)==8 && array_search($ligne[4], $tabRne, true)===false) $tabRne[] = $ligne[4];
		}
		$user->setFonctions($tabFct);
		$user->setEtablissements($tabRne);
		// Persistance
		$this->em->persist($user);
		$this->em->flush();

		//--> Construction des rôles en fonction du champ AttributApplicationLocale et de l'état du compte
		// 	Note : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.
		// Par défaut tout le monde des user
		$roles = array();
		// En fonction du profil de l'attribut d'application locale CHARTECA
		$tabAttributs = $tabAttribs["attributapplicationlocale"];
		// Lire attribut par attribut
		for ($x=0 ; $x<count($tabAttributs) ; $x++) 
		{
			// On regarde chaque attribut et on le sépare en morceaux
			$tmp = explode ("|", $tabAttributs[$x]);
			// On vérifie bien que c'esu un attribut pour le guichet
			if (isset($tmp[0]) && isset($tmp[1]) && $tmp[0]=="CHARTECA" && $tmp[1]=="ADMIN") $roles[] = "ROLE_ADMIN";
			if (isset($tmp[0]) && isset($tmp[1]) && $tmp[0]=="CHARTECA" && $tmp[1]=="MODERATEUR") $roles[] = "ROLE_MODERATEUR";
			if (isset($tmp[0]) && isset($tmp[1]) && $tmp[0]=="CHARTECA" && $tmp[1]=="ASSISTANCE") $roles[] = "ROLE_ASSISTANCE";
		}
		// On mémorise les rôles das l'objet User
		$user->setRoles($roles);

		//--> Génération des statistiques en utilisant le role maximum.
		// 		uniquement sur l'Application principale, pas de statistiques sur les autres bundles (SimulRSA, profiler,statistiques etc....) 
		//		et uniquement le controleur DefaultController, ce qui évite de logguer les deconnexion (AppBundle\Controller\SecurtiotyController)
		$request = $this->requestStack->getCurrentRequest();
		if (preg_match("/^AppBundle\\\\Controller\\\\DefaultController/", $request->attributes->get('_controller')))
		{
			$maxProfil = "ROLE_USER";
			if (in_array("ROLE_ASSISTANCE", $roles, true)) $maxProfil = "ROLE_ASSISTANCE";
			if (in_array("ROLE_MODERATEUR", $roles, true)) $maxProfil = "ROLE_MODERATEUR";
			if (in_array("ROLE_ADMIN", $roles, true)) $maxProfil = "ROLE_ADMIN";
			$this->stats->incStats($maxProfil);
		}
	}

}

?>
