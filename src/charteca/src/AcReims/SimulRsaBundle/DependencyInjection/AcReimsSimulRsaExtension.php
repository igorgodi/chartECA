<?php

namespace AcReims\SimulRsaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AcReimsSimulRsaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

	// Récupération config
        if (isset($config['ldapHost'])) $container->setParameter('ac_reims_simul_rsa.ldapHost', $config['ldapHost']);
        if (isset($config['ldapPort'])) $container->setParameter('ac_reims_simul_rsa.ldapPort', $config['ldapPort']);
        if (isset($config['ldapReaderDn'])) $container->setParameter('ac_reims_simul_rsa.ldapReaderDn', $config['ldapReaderDn']);
        if (isset($config['ldapReaderPw'])) $container->setParameter('ac_reims_simul_rsa.ldapReaderPw', $config['ldapReaderPw']);
        if (isset($config['ldapRacine'])) $container->setParameter('ac_reims_simul_rsa.ldapRacine', $config['ldapRacine']);

    }
}
