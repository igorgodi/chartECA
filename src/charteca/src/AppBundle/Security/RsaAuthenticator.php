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

namespace AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\EventListener\RsaAttributs;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Gestion du firewall symfony en s'appuyant sur le composant Guard
 * 	Ce firewall est configuré dans app/config/security.yml
 *		voir https://symfony.com/doc/current/security/guard_authentication.html
*/
class RsaAuthenticator extends AbstractGuardAuthenticator
{
	/** Objet de type RsaAttributs */
	private $rsa;

	/**
	 * Constructeur
	 *
	 * @param $rsa Objet permettant de traiter les champs RSA et créer ou mettre à jour l'utilisateur associé.
	 */
	public function __construct(RsaAttributs $rsa)
	{
		$this->rsa = $rsa;
	}

	/**
	 * Méthode appelée à chaque requête. Retourne le nom d'utilisateur
	 * qui sera passé à la méthode getUser(). Si on retourne null, l'authentification
	 * est stoppée (si anonymous est à true dans security.yml, ceci permet le mode anonyme)
	 */
	public function getCredentials(Request $request)
	{
		//--> Non d'utilisateur transmis à la méthode getUser() dans le paramètre $credentials
		//	$this->rsa->getUser() retourne un objet de type User, cette méthode se charge aussi de la création de l'utilisateur dans l'entité
		return array('username' => $this->rsa->getUser()->getUsername());
	}

	/**
	 * Méthode appelée si on n'est pas en anonyme
	 */
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

	/**
	 * Méthode de vérification du mot de passe
	 */
	public function checkCredentials($credentials, UserInterface $user)
	{
		// On est en RSA donc on ne vérifie pas, c'est RSA qui s'en est chargé
		return true;
	}

	/**
	 * Méthode appelée en cas de succès à l'authentification
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
	{
		// En cas de succes on continue
		return null;
	}

	/**
	 * Méthode appelée en cas d'échec à l'authentification
	 */
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
	{
		return new Response("Echec authentification RSA", Response::HTTP_FORBIDDEN);
	}

	/**
	 * Méthode appelée si l'authentification est nécessaire mais pas invoquée
	 */
	public function start(Request $request, AuthenticationException $authException = null)
	{
		return new Response("Authentification RSA obligatoire", Response::HTTP_UNAUTHORIZED);
	}

	/**
	 * Méthode retournant true si le support 'Se souvenir de moi' est activé
	 */
	public function supportsRememberMe()
	{
		return false;
	}
}
?>
