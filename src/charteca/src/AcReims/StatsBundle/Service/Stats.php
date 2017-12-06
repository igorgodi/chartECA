<?php
/*
 *   Service de gestion des statistiques utilisateurs
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

namespace AcReims\StatsBundle\Service;

use AcReims\StatsBundle\Entity\Stat;

use Doctrine\ORM\EntityManagerInterface;

use Synfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * Classe de gestion des statistiques
 */
class Stats implements StatsInterface
{
	/** Gestionnaire d'entités doctrine */
	private $em;

	/** Objet de session */
	private $session;

	/**
	 * Constructeur
	 *
	 * @param $em Gestionnaire d'entités doctrine
	 */
	public function __construct(EntityManagerInterface $em, Session $session)
	{
		// Sauvegarde des objets
		$this->em = $em;
		$this->session = $session;
	}
 
	/**
	 * Ecrire une entrée statistique corrspondant à ce profil
	 *
	 * @param $profil Profil de l'utilisateur
	 */
	public function incStats($profil)
	{
		//--> Vérification de validité
		if ( $profil==null || $profil=="" ) throw new InvalidArgumentException("Stats::incStats() : Le profil ne doit pas être null ou vide");

		//--> Avons-nous déjà été attrapé par les stats dans ce profil ???
		$sess = $this->session->get("_statistiques_profil_$profil", null); 
		if ($sess != null || $sess == true) return;
		$this->session->set("_statistiques_profil_$profil", true);

		//--> Lecture de l'état des statistiques horaire pour ce profil
		$stat = $this->em->getRepository('AcReimsStatsBundle:Stat')->lireStat($profil);
		// Si elle existe, on incrémente
		if ($stat) 
		{
			$stat = $stat[0]; // Le resultat étant un tableau, il faut récupérer le premier élèment
			$stat->setCpt( ($stat->getCpt()+1) );
		}
		// Sinon on crée (par défaut le constructeur de l'entité initialise les champs annee, mois, jour, heure, cpt par défaut et profil par l'argument)
		else $stat = new Stat($profil);

		//--> Persister la statistique
		$this->em->persist($stat);
		$this->em->flush();
	}

}
