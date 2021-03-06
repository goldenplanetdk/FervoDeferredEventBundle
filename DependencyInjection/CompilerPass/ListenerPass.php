<?php

namespace Fervo\DeferredEventBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('fervo_deferred_event.dispatcher.real'))
        {
            return;
        }

        $fervoDispatcherDef = $container->getDefinition('fervo_deferred_event.dispatcher.real');
        $sfDispatcherDef = $container->findDefinition('event_dispatcher');

        $listenerTags = $container->findTaggedServiceIds('fervo_deferred_event.listener');
        foreach ($listenerTags as $id => $tags) {
            foreach ($tags as $tag) {
                $deferredEvents[] = $tag['event'];

                $callArgs = [
                    $tag['event'],
                    [new Reference($id), $tag['method']],
                ];

                if (isset($tag['priority'])) {
                    $callArgs[] = $tag['priority'];
                }

                $fervoDispatcherDef->addMethodCall('addListener', $callArgs);
            }
        }

        //if we don't got any deferredEvents..
        if (empty($deferredEvents)) {
            return;
        }

        $deferredEvents = array_unique($deferredEvents);

        foreach ($deferredEvents as $event) {
            $sfDispatcherDef->addMethodCall(
                'addListener', [
                    $event,
                    ['fervo_deferred_event.listener', 'onNonDeferEvent'],
                    -127,
                ]
            );
        }
    }
}
