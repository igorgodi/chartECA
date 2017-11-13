<?php
/*
 *   Formulaire de modération d'une demande d'utilisation de ECA
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


namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Déclaration du formulaire
 */
class ValidDemandeUtilisationEcaType extends AbstractType
{
	/**
	 * Générateur de formulaire
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
		  ->add('motifRefus',   TextareaType::class, 	array('label' => 'Motif du refus (si refus)', 'required' => false))
		  ->add('validation', 	CheckboxType::class, 	array('label' => 'Je suis sûr', 'required' => false))
		  ->add('save',      	SubmitType::class, 	array('label' => 'Enregistrer ma modération'));
	}

	/**
	 * Resolveur permettant de lier à l'entité
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AppBundle\EntityNoPersist\ValidDemandeUtilisationEca'
		));
	}
}
