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

	// https://symfony.com/doc/current/components/config/definition.html
	// https://symfony.com/doc/current/bundles/configuration.html
	$rootNode
            ->children()
                ->scalarNode('ldapHost')
			->defaultValue('127.0.0.1')
			->isRequired()
			->cannotBeEmpty()
		->end()
                ->scalarNode('ldapPort')
			->defaultValue('389')
			->isRequired()
			->cannotBeEmpty()
		->end()
                ->scalarNode('ldapReaderDn')
			->defaultValue('uid=consult, ou=special users, o=gouv, c=fr')
			->isRequired()
			->cannotBeEmpty()
		->end()
                ->scalarNode('ldapReaderPw')
			->defaultValue('xxxxxxxxx')
			->isRequired()
			->cannotBeEmpty()
		->end()
                ->scalarNode('ldapRacine')
			->defaultValue('o=gouv, c=fr')
			->isRequired()
			->cannotBeEmpty()
		->end()
             ->end();
        return $treeBuilder;
    }
}
