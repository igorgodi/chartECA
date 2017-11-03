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
	private $user = null;

	public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
	{
		$this->em = $em;
		$this->logger = $logger;
	}

	// TODO : comment
	public function getUser()
	{
		// On ne traite ceci que si l'objet n'existait pas encore
		if ($this->user == null)
		{	
			//--> Récupération de l'attribut RSA ct-remote-user
			// TODO : recup username via attributs RSA
			$username = "igor";

			//--> TODO : tester ici les attributs RSA obligatoires (ct-remote-user, ctemail,....) :
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

			//--> Vérifier si l'utilisateur existe dans la base et autocreate si besoin
			$this->user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);
			if (!$this->user)
			{
				$this->logger->info("Création de l'utilisateur '$username' non présent dans la table des utilisateurs");
				$this->user = new User();
				$this->user->setUsername($username);
				$this->em->persist($this->user);
				$this->em->flush();
			}
			else $this->logger->info("Utilisateur '$username' déjà présent dans la table des utilisateurs");

			//--> TODO : la présence du champ AttributApplicationLocale est optionnel car les personnes n'ayant encore aucune habilitation ne l'on pas.

			//--> Attributs de l'objet hors persistance
			$this->user->setRoles(array("ROLE_USER", "ROLE_MODERATEUR"));
			//$this->user->setRoles(array("ROLE_USER"));
		}

		//--> Retourne l'objet user
		return ($this->user);

	}
}
?>
