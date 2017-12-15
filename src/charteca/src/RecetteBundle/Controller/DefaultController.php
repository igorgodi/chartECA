<?php
/*
 *   Controleur chargé de gérer les pages de lancement des scripts de recette en ligne de commande
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

namespace RecetteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;


/**
 * Définition de la route principale du contrôleur :
 * @Route("/_recette")
 */
class DefaultController extends Controller
{
	/**
	 * Page d'execution d'une commande app:.... . 
	 * La liste des commandes disponibles pour la recette est à régler dans le 
	 *	requirements de //@Route("....... , requirements={"cmd" = "cron|recette:cleanbase|......"}, ........")
	 * 
	 * https://symfony.com/doc/3.3/console/command_in_controller.html
	 * http://benjamin.leveque.me/symfony2-executer-une-commande-depuis-un-controller.html
	 *
	 * @Route("/{cmd}", requirements={"cmd" = "cron|recette:cleanbase"}, name="_recette_exec_cmd")
	 */
	public function cleanbaseAction($cmd)
	{
		//Récupère l'objet du noyau
		$kernel = $this->get('kernel');

		$application = new Application($kernel);
		$application->setAutoExit(false);

		$input = new ArrayInput(array(
		   'command' => "app:$cmd",
		   // (optional) define the value of command arguments
		   //'fooArgument' => 'barValue',
		   // (optional) pass options to the command
		   '--env' => $kernel->getEnvironment(),
		));

		// You can use NullOutput() if you don't need the output
		$output = new BufferedOutput();
		$error_code = $application->run($input, $output);

		// return the output, don't use if you used NullOutput()
		$c = $output->fetch();
		$c = preg_replace ("/\n/", "<br />", $c); 
		$content = "Environnement='" . $kernel->getEnvironment() . "' ; commande = 'app:$cmd'<hr />Contenu retour :<br />"  . $c ."<hr />Terminé.<br /><br />code_retour = $error_code";
		// return new Response(""), if you used NullOutput()
		return new Response($content);	
	}
}
