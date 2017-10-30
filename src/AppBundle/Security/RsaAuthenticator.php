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
		// TODO : recup username via attributs RSA
		$username = "igor";
		// TODO : Si l'attribut RSA n'est pas trouvé on loggue
		if (0 /* .....*/) 
		{
			$this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
			return "";
		}

		// What you return here will be passed to getUser() as $credentials
		return array(
		    'username' => $username,
		);
	}

	public function getUser($credentials, UserProviderInterface $userProvider)
	{
		// Récupère le nom d'utilisateur
		if (!isset($credentials['username']) || $credentials['username']===null) throw new AuthenticationException("Erreur authentification");	
		$username = $credentials['username'];

		// Vérifier si l'utilisateur existe dans le base et autocreate si besoin
		$user = $this->em->getRepository('AppBundle:User')->findOneBy(array("username"=>$username));
		//dump($user);
		if (!$user)
		{
			$user = new User();
			$user->setUsername($username);
			$this->em->persist($user);
			$this->em->flush();
		}
		// Mise à jour hors persistance
		// TODO : recup via attributs RSA
		// TODO : si erreur récup attributs RSA :
		if (0 /*.....*/) 
		{
			$this->logger->critical("Attribut RSA 'toto' non trouvé");
			throw new AuthenticationException("Erreur authentification");
		}	
		//$user->setRoles(array("ROLE_USER", "ROLE_ADMIN"));
		$user->setRoles(array("ROLE_USER"));

		// Retourne l'objet user trouvé ou créé
		//return $userProvider->loadUserByUsername($username);
		return $user;
	}

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
		return new Response("Erreur d'authentification Guard", Response::HTTP_FORBIDDEN);
	}

	/**
	* Called when authentication is needed, but it's not sent
	*/
	public function start(Request $request, AuthenticationException $authException = null)
	{
		return new Response("Authentification requise : erreur Guard", Response::HTTP_UNAUTHORIZED);
	}

	public function supportsRememberMe()
	{
		return false;
	}
}
?>
