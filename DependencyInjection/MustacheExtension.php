<?php

/*
 * This file is part of the Mustache.php bundle for Symfony2.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bobthecow\Bundle\BobthecowMustacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * MustacheExtension.
 */
class MustacheExtension extends Extension
{
    /**
     * Responds to the mustache configuration parameter.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('mustache.xml');

        foreach ($configs as &$config) {
            if (isset($config['globals'])) {
                foreach ($config['globals'] as $name => $value) {
                    if (is_array($value) && isset($value['key'])) {
                        $config['globals'][$name] = array(
                            'key'   => $name,
                            'value' => $config['globals'][$name]
                        );
                    }
                }
            }
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['loader'])) {
            $config['loader'] = new Reference($config['loader']);
        } else {
            $config['loader'] = $container->getDefinition('mustache.loader');
        }

        if (isset($config['partials_loader'])) {
            $config['partials_loader'] = new Reference($config['partials_loader']);
        } else {
            $config['partials_loader'] = $config['loader'];
        }

        if (!empty($config['globals'])) {
            $def = $container->getDefinition('mustache');
            foreach ($config['globals'] as $key => $global) {
                if (isset($global['type']) && 'service' === $global['type']) {
                    $def->addMethodCall('addHelper', array($key, new Reference($global['id'])));
                } else {
                    $def->addMethodCall('addHelper', array($key, $global['value']));
                }
            }
        }

        unset($config['globals']);

        $container->setParameter('mustache.options', $config);

        $this->addClassesToCompile(array(
            'Mustache_Context',
            'Mustache_HelperCollection',
            'Mustache_Loader',
            'Mustache_Loader_FilesystemLoader',
            'Mustache_Mustache',
            'Mustache_Template',
        ));
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://justinhileman.info/schema/dic/mustache';
    }
}