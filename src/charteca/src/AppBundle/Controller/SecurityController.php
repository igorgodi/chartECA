<?php

// TODO : comment

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

// TODO : comment
class SecurityController extends Controller
{
	/**
	* @Route("/logout", name="logout")
	*/
	public function logoutAction()
	{
		// Destruction de session
		$this->get('security.context')->setToken(null);
		$this->get('request')->getSession()->invalidate(); 
	}

	/**
	* @Route("/rsa_deconnect", name="rsa_deconnect")
	*/
	public function rsaDecoAction()
	{
		// Redirection vers logout RSA
		return new RedirectResponse($this->container->getParameter('rsaDeco'));

	}

}
