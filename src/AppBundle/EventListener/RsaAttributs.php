<?php

// TODO : comment
namespace AppBundle\EventListener;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RsaAttributs
{
	// TODO comment
	private $em;
	private $session;
	private $logger;

	public function __construct(EntityManagerInterface $em, SessionInterface $session, LoggerInterface $logger)
	{
		$this->em = $em;
		$this->session = $session;
		$this->logger = $logger;
	}

	// TODO : comment
	// Load User est chargé automatiquement lors de l'évenement kernel.request (voir app/config/services.yml)
	public function loadUser()
	{
		//--> Récupération de l'attribut RSA ct-remote-user
		// TODO : recup username via attributs RSA
		$username = "igor";

		// On vide la variable de session afin de régénérer la lecture
		$this->session->remove('user');

		//--> TODO : tester ici les attributs RSA obligatoires (ct-remote-user, ctemail,....) :
		if ($username == "" || 0 /* .....*/) 
		{
			// On loggue
			$this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
		}
		else
		{
			//--> Vérifier si l'utilisateur existe dans la base et autocreate si besoin
			$user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);
			if (!$user)
			{
				$this->logger->info("Création de l'utilisateur '$username' non présent dans la table des utilisateurs");
				$user = new User();
				$user->setUsername($username);
				$user->setEtatCompte(User::ETAT_COMPTE_INACTIF);	// Note : déjà fait par défaut dans l'entité
				$this->em->persist($user);
				$this->em->flush();
			}
			else $this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");

			//--> Construction des rôles en fonction du champ AttributApplicationLocale et de l'état du compte
			// 	Note : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.
			// Par défaut tout le monde des user
			$roles = array("ROLE_USER");
			// TODO : En fonction du profil de l'attribut d'application locale CHARTECA
			if (1) $roles[] = "ROLE_ASSISTANCE";
			if (1) $roles[] = "ROLE_MODERATEUR";
			if (1) $roles[] = "ROLE_ADMIN";
			// En fonction de l'état du compte
			if ($user->getEtatCompte() == User::ETAT_COMPTE_INACTIF) $roles[] = "ROLE_USER_INACTIF";
			if ($user->getEtatCompte() == User::ETAT_COMPTE_ATTENTE_ACTIVATION) $roles[] = "ROLE_USER_ATTENTE_ACTIVATION";
			if ($user->getEtatCompte() == User::ETAT_COMPTE_ACTIF) $roles[] = "ROLE_USER_ACTIF";
			// Enregistrer les rôles
			$user->setRoles($roles);

			//--> Ranger l'utilisateur en session
			$this->session->set('user', $user);

		}
	}

	// TODO : comment
	// Penser à commenter le Throw
	public function getUser()
	{
		$user = $this->session->get('user', null);

		// Si erreur de lecture des attributs
		// http://api.symfony.com/2.7/Symfony/Component/HttpKernel/Exception/HttpException.html
		if ($user == null) throw new HttpException(401, "Erreur lecture des attributs RSA");

		return ($user);
	}
}
?>
