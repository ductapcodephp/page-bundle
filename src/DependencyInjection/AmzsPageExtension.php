<?php

namespace AmzsCMS\TopicBundle\DependencyInjection;


use AmzsCMS\TopicBundle\Constant\PageRoute;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AmzsArticleExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $container->setParameter('amz.user_bundle.default_password', $config['default_password']);
    }
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('twig', [
            'globals' => [
                'amzs_topic_index_route' => PageRoute::ROUTE_INDEX,
                'amzs_topic_data_route' => PageRoute::ROUTE_DATA,
                'amzs_topic_add_route' => PageRoute::ROUTE_ADD,
                'amzs_topic_edit_route' => PageRoute::ROUTE_EDIT,
                'amzs_topic_delete_route' => PageRoute::ROUTE_DELETE,
            ],
        ]);
    }
}