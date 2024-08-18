<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class TransactionalOutboxBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $treeBuilder = new TreeBuilder('queues');
        $node = $treeBuilder->getRootNode();

        $nodeChildren = $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
        ;

        $nodeChildren->scalarNode('connection_name')->end();
        $nodeChildren->scalarNode('table_name')->end();

        $definition->rootNode()
            ->children()
                ->append($node)
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if (empty($config['queues'])) {
            $config['queues'] = [
                'default' => [
                    'connection_name' => 'default',
                    'table_name' => 'outbox_queue',
                ],
            ];
        }

        $container->parameters()
            ->set('transactional_outbox.queues', $config['queues'])
        ;
    }
}
