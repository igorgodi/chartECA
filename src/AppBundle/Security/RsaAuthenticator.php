<?php
// TODO : comment
namespace AppBundle\Security;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

// TODO comment
// https://symfony.com/doc/current/security/guard_authentication.html

class RsaAuthenticator extends AbstractGuardAuthenticator
{
	// TODO comment
	private $em;
	private $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
	{
		$this->em = $em;
		$this->logger = $logger;
	}

	/**
	* Called on every request. Return whatever credentials you want to
	* be passed to getUser(). Returning null will cause this authenticator
	* to be skipped.
	*/
	public function getCredentials(Request $request)
	{
		// TODO : prévoir l'accès anonyme ???

		//--> Récupération de l'attribut RSA ct-remote-user
		// TODO : recup username via attributs RSA
		$username = "igor";
		// TODO : Si l'attribut RSA n'est pas trouvé on loggue
		if (0 /* .....*/) 
		{
			$this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
			return array('username' => null);
		}

		//--> Non d'utilisateur transmis à la méthode getUser() dans le parmètre $credentials
		return array('username' => $username);
	}

	// TODO : comment
	public function getUser($credentials, UserProviderInterface $userProvider)
	{
		//--> Vérifie que le nom d'utilisateur à bien été transmis, si null, pb attribut ct-remote-user
		// 	Et l'accès anonyme étant interdit dans cette configuration, on lève l'interruption
		if ($credentials['username']===null) throw new AuthenticationException("Erreur authentification credential=null");	

		// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
		$user = $this->em->getRepository('AppBundle:User')->findOneBy(array("username"=>$credentials['username']));
		if (!$user)
		{
			$user = new User();
			$user->setUsername($credentials['username']);
			$this->em->persist($user);
			$this->em->flush();
		}
		//--> Récupération des attributs RSA nécessaires avec levé d'interruption et journalisation si erreur
		// TODO : Le champ ctemail est obligatoire
		// TODO : si erreur récup attributs RSA :
		if (0 /*.....*/) 
		{
			$this->logger->critical("Attribut RSA 'toto' non trouvé");
			throw new AuthenticationException("Erreur authentification");
		}	
		// TODO : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.

		//--> Mise à jour de l'objet $user hors persistance
		// Définition des rôles de la personne : le rôle mini pour tous est ROLE_USER
		$user->setRoles(array("ROLE_USER", "ROLE_MODERATEUR"));
		//$user->setRoles(array("ROLE_USER"));

		//--> Retourne l'objet $user trouvé ou créé
		//return $userProvider->loadUserByUsername($credentials['username']);
		return $user;
	}

	// TODO : comment
	public function checkCredentials($credentials, UserInterface $user)
	{
		// check credentials - e.g. make sure the password is valid
		// no credential check is needed in this case

		// return true to cause authentication success
		return true;
	}

	public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
	{
		// on success, let the request continue
		return null;
	}

	public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
	{
		$this->logger->critical("Echec Authentification RSA");
		return new Response("Echec authentification RSA", Response::HTTP_FORBIDDEN);
	}

	/**
	* Called when authentication is needed, but it's not sent
	*/
	public function start(Request $request, AuthenticationException $authException = null)
	{
		$this->logger->critical("Authentification RSA obligatoire");
		return new Response("Authentification RSA obligatoire", Response::HTTP_UNAUTHORIZED);
	}

	public function supportsRememberMe()
	{
		return false;
	}
}
?>
