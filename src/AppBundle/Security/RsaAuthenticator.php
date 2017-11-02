<?php
// TODO : comment
namespace AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Service\RsaAttributs;

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
	private $rsa;

	public function __construct(RsaAttributs $rsa)
	{
		$this->rsa = $rsa;
	}

	/**
	* Called on every request. Return whatever credentials you want to
	* be passed to getUser(). Returning null will cause this authenticator
	* to be skipped.
	*/
	public function getCredentials(Request $request)
	{
		//--> Non d'utilisateur transmis à la méthode getUser() dans le paramètre $credentials
		//	$this->rsa->getUser() retourne un objet de type User, cette méthode se charge aussi de la création de l'utilisateur dans l'entité
		return array('username' => $this->rsa->getUser()->getUsername());
	}

	// TODO : comment
	public function getUser($credentials, UserProviderInterface $userProvider)
	{
		//--> Vérifie que le nom d'utilisateur à bien été transmis, si null, pb attribut ct-remote-user
		// 	Et l'accès anonyme étant interdit dans cette configuration, on lève l'interruption
		if ($credentials['username']===null) throw new AuthenticationException("Erreur authentification credential=null");	

		//--> Rétourner l'objet $user
		// Méthode 1 : via $userProvider : ça marche même avec les attributs non persistants
		//return ($userProvider->loadUserByUsername($credentials['username']));
		// Méthode 2 : Récuperer l'objet $user via le service RSA getUser() : cette méthode évite à la méthode 1 de rappeler un select doctrine.
		return($this->rsa->getUser()); 
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
		return new Response("Echec authentification RSA", Response::HTTP_FORBIDDEN);
	}

	/**
	* Called when authentication is needed, but it's not sent
	*/
	public function start(Request $request, AuthenticationException $authException = null)
	{
		return new Response("Authentification RSA obligatoire", Response::HTTP_UNAUTHORIZED);
	}

	public function supportsRememberMe()
	{
		return false;
	}
}
?>
