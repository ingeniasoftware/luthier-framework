<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Http\EventSubscriber;

use Luthier\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Request related events used internally by Luthier Framework
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RequestSubscriber implements EventSubscriberInterface  
{
    /**
     * @param Events\RequestEvent $event
     */
    public function onRequest(Events\RequestEvent $event)
    {
        $request = $event->getRequest();
        
        if (strtolower($request->getMethod()) == "post" && !$request->isAjax()) {
            $method = $request->post("_method");
            if (!empty($method)) {
                $request->setMethod($method);
            }
        }
    }
    
    public static function getSubscribedEvents()
    {
        return [
            'request' => ['onRequest', 0],
        ];
    }
}