<?php
/*
 *   Formulaire de choix de l'utilisateur dans SimulRSA
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


namespace AcReims\SimulRsaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Déclaration du formulaire
 */
class ChoixUserType extends AbstractType
{
	/**
	 * Générateur de formulaire
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			// TODO : enlever les required ensuite car permet la prévalidation HTML5
		  ->add('uid',   TextType::class, 	array('label' => 'Uid à simuler (vide annuler)', 
								'required' => false))
		  ->add('save',  SubmitType::class, 	array('label' => 'Valider',
								'attr' => array('onclick' => 'return confirm("Etes-vous sûr ?")'))
			);
	}

	/**
	 * Resolveur permettant de lier à l'entité
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AcReims\SimulRsaBundle\Entity\NoPersist\ChoixUser'
		));
	}
}
