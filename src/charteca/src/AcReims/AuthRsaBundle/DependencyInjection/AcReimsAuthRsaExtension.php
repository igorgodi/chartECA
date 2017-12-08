<?php

namespace AcReims\AuthRsaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AcReimsAuthRsaExtension extends Extension
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

	// Récupération configuration dans les variables
        if (isset($config['actif'])) $container->setParameter('ac_reims_auth_rsa.actif', $config['actif']);
	if (isset($config['attributs'])) $container->setParameter('ac_reims_auth_rsa.attributs',$config['attributs']);
        else $container->setParameter('ac_reims_auth_rsa.attributs',array());
    }
}
