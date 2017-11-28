<?php

namespace AcReims\SimulRsaBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('ac_reims_simul_rsa');

	// TODO : voir comment rendre obligatoire
	$rootNode
            ->children()
                ->scalarNode('ldapHost')->end()
                ->scalarNode('ldapPort')->end()
                ->scalarNode('ldapReaderDn')->end()
                ->scalarNode('ldapReaderPw')->end()
                ->scalarNode('ldapRacine')->end()
             ->end();

        return $treeBuilder;
    }
}
