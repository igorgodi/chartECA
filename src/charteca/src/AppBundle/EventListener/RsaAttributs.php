<?php

// TODO : comment
namespace AppBundle\EventListener;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RsaAttributs
{
	// TODO comment
	private $requestStack;
	private $em;
	private $session;
	private $logger;

	public function __construct(RequestStack $requestStack, EntityManagerInterface $em, SessionInterface $session, LoggerInterface $logger)
	{
		$this->requestStack = $requestStack;
		$this->em = $em;
		$this->session = $session;
		$this->logger = $logger;
	}

	// TODO : comment
	// Load User est chargé automatiquement lors de l'évenement kernel.request (voir app/config/services.yml)
	public function loadUser()
	{
		//--> On vide la variable de session afin de régénérer la lecture
		$this->session->remove('user');

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
			if ($cn == "") $this->logger->critical("Attribut RSA 'cnl' non trouvé");
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
				$user->setEmail($email);
				$user->setEtatCompte(User::ETAT_COMPTE_INACTIF);	// Note : déjà fait par défaut dans l'entité
				$this->em->persist($user);
				$this->em->flush();
			}
			else 
			{
				$this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");
				$user->setEmail($email);	// Seul l'email est synchronisé car c'est le seul qui est persistance avec le username et l'état du compte
				$this->em->persist($user);
				$this->em->flush();
			}

			//--> On mémorise le cn pour l'affichage
			$user->setCn($cn);

			//--> Construction des rôles en fonction du champ AttributApplicationLocale et de l'état du compte
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
		if ($user == null) throw new HttpException(500, "Erreur lecture des attributs RSA");

		return ($user);
	}
}
?>
