<?php

// TODO : comment
namespace AppBundle\Service;

use AppBundle\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RsaAttributs
{
	// TODO comment
	private $em;
	private $logger;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
	{
		$this->em = $em;
		$this->logger = $logger;
	}

	// TODO : comment
	public function getUser()
	{
		
		//--> Récupération de l'attribut RSA ct-remote-user
		// TODO : recup username via attributs RSA
		$username = "igor";

		// TODO : tester ici les attributs RSA obligatoires (ct-remote-user, ctemail,....) :
		if (0 /* .....*/) 
		{
			// On loggue
			$this->logger->critical("Attribut RSA 'ct-remote-user' non trouvé");
			// Puis ecception
			// http://api.symfony.com/2.7/Symfony/Component/HttpKernel/Exception/HttpException.html
			throw new HttpException(401, "Erreur attribut RSA 'ct-remote-user' requis");
			// TODO : utile ????
			return array('username' => null);
		}

		// Vérifier si l'utilisateur existe dans la base et autocreate si besoin
		$user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);
		if (!$user)
		{
			$this->logger->info("Création de l'utilisateur '$username' non présent dans la table des utilisateurs");
			$user = new User();
			$user->setUsername($username);
			$this->em->persist($user);
			$this->em->flush();
		}
		else $this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");

		// TODO : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.

		// Attributs de l'objet hors persistance
		$user->setRoles(array("ROLE_USER", "ROLE_MODERATEUR"));
		//$this->user->setRoles(array("ROLE_USER"));


		// Retourne le username
		return $user;

	}

}
?>
