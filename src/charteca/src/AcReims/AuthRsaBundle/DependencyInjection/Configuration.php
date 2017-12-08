<?php

namespace AcReims\AuthRsaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ac_reims_auth_rsa');

	// https://symfony.com/doc/current/components/config/definition.html
	// https://symfony.com/doc/current/bundles/configuration.html
	// http://api.symfony.com/2.7/Symfony/Component/Config/Definition/Builder/TreeBuilder.html
	// Définition des noeuds : http://api.symfony.com/2.7/Symfony/Component/Config/Definition/Builder/ArrayNodeDefinition.html
	$rootNode
            ->children()
		// Booléen d'activation
		->booleanNode('actif')
			->info('Activer la lecture des attributs RSA')
            		->defaultFalse()
		->end()
           ->end()

            ->children()
                ->arrayNode('attributs')
			->info('Attributs à récupérer dans RSA')
		        ->useAttributeAsKey('nom')
		        ->prototype('array')
		            ->children()
		                ->booleanNode('obligatoire')->defaultFalse()->end()
		                ->booleanNode('multivalue')->defaultFalse()->end()
		            ->end()
		 ->end()
             ->end();
        return $treeBuilder;
    }
}
