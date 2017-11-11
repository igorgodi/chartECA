<?php
/*
 *   Ecouteur d'évenement de gestion de la récupération des attributs RSA pour gérer les utilisateurs de l'application
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

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Classe de gestion de la collecte des attributs RSA et l'enregistrement de l'utilsateur
 * 	Peut-être utilisée :
 *		- directement dans l'application (gestion habilitations par directement par RSA)
 *		- ou transferé au firewall Guard qui gérera les habilitations dans l'application
 */
class RsaAttributs
{
	/** Oblet logger */
	private $logger;

	/** Pile de requêtes */
	private $requestStack;

	/** Gestionnaire d'entités doctrine */
	private $em;

	/** Objet de type User */
	private $user=null;

	/**
	 * Constructeur
	 *
	 * @param $logger Objet logger
	 * @param $requestStack Objet de pile de requête
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(LoggerInterface $logger, RequestStack $requestStack, EntityManagerInterface $em)
	{
		// Sauvegarde des objets
		$this->logger = $logger;
		$this->requestStack = $requestStack;
		$this->em = $em;
	}


	/**
	 * Méthode de chargement de l'utilsateur. Appelé par l'évènement kernel.request (voir app/config/services.yml)
	 */
	public function loadUser()
	{
		//--> Récupère la requête courante dans le service
		$request = $this->requestStack->getCurrentRequest();

		//--> Récupération des attributs RSA nécessaires à l'application
		$username = $request->headers->get("ct-remote-user", "");
		$email = $request->headers->get("ctemail", "");
		$cn = $request->headers->get("cn", "");
		$attributsApplicationLocale = $request->headers->get("AttributApplicationLocale", "");

		//--> Tester les attributs RSA obligatoires (ct-remote-user, ctemail,....) :
		if ($username == "" || $email == "" || $cn == "" ) 
		{
			// On loggue
			if ($username == "") $this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
			if ($email == "") $this->logger->critical("Attribut RSA 'ctemail' non trouvé");
			if ($cn == "") $this->logger->critical("Attribut RSA 'cn' non trouvé");
			// On sort à la moindre erreur
			return;
		}

		//--> Création ou correction de l'utilisateur dans l'entité User
		$this->user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);
		if (!$this->user)
		{
			$this->logger->info("Création de l'utilisateur '$username' non présent dans la table des utilisateurs");
			$this->user = new User();
			$this->user->setUsername($username);
			$this->user->setEmail($email);
			$this->user->setEtatCompte(User::ETAT_COMPTE_INACTIF);	// Note : déjà fait par défaut dans l'entité
		}
		else 
		{
			$this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");
			$this->user->setEmail($email);	// Seul l'email est synchronisé car c'est le seul qui est persistance avec le username et l'état du compte
		}
		$this->em->persist($this->user);
		$this->em->flush();

		//--> Ajout des élèments de l'objet User hors persistance 
		// 1/ On mémorise le cn pour l'affichage
		$this->user->setCn($cn);

		// 2/ Construction des rôles en fonction du champ AttributApplicationLocale et de l'état du compte
		// 	Note : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.
		// Par défaut tout le monde des user
		$roles = array("ROLE_USER");
		// En fonction du profil de l'attribut d'application locale CHARTECA
		$tabAttributs = array();		// Tableau des attributs locaux
		$tabAttributs = explode (",", $attributsApplicationLocale);
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
		// Construction des rôles en fonction de l'état du compte
		if ($this->user->getEtatCompte() == User::ETAT_COMPTE_INACTIF) $roles[] = "ROLE_USER_INACTIF";
		if ($this->user->getEtatCompte() == User::ETAT_COMPTE_ATTENTE_ACTIVATION) $roles[] = "ROLE_USER_ATTENTE_ACTIVATION";
		if ($this->user->getEtatCompte() == User::ETAT_COMPTE_ACTIF) $roles[] = "ROLE_USER_ACTIF";
		// 3/ On mémorise les rôles das l'objet User
		$this->user->setRoles($roles);
	}

	/**
	 * Méthode getter de l'utilisateur
	 *
	 * @return Retourne un objet de type User
	 */
	public function getUser()
	{
		//--> Si il n'est pas encore chargé, le charger
		if ($this->user == null) $this->loadUser();

		//-> Si erreur de lecture des attributs dans la phase précédente, on envoi une exception erreur 500 qui va 
		//	- Interrompre l'action en cours
		//	- générer un log critical et donc un mail d'erreur en production.
		// 		voir http://api.symfony.com/2.7/Symfony/Component/HttpKernel/Exception/HttpException.html
		if ($this->user == null) throw new HttpException(500, "Erreur lecture des attributs RSA");

		// Retourne l'objet de type User
		return ($this->user);
	}
}
?>
