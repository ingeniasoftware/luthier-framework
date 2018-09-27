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
        $this->setRequestMethodFromField($request);
        $this->parseJsonBody($request);
    }
    
    /**
     * If the request is a POST request and contains a field named "_method"
     * we will set the request method to the "_method" field value, allowing 
     * us to use other methods than GET and POST via traditional html forms
     * 
     * @param \Luthier\Http\Request $request
     * @internal
     */
    private function setRequestMethodFromField($request)
    {
        if (strtolower($request->getMethod()) == "post" && !$request->isAjax()) {
            $method = $request->post("_method");
            if (!empty($method)) {
                $request->setMethod(strtoupper($method));
            }
        }
    }
    
    /**
     * JSON body parsing. Useful when we recieve a request with a JSON 
     * payload (Content-Type: application/json).
     * 
     * @param \Luthier\Http\Request $request
     * @internal
     */
    private function parseJsonBody($request)
    {
       if ($request->getContentType() === 'json') {
            $jsonBody = json_decode($request->getContent(), true);
            $request->getRequest()->request->add($jsonBody);
       }
    }
    
    public static function getSubscribedEvents()
    {
        return [
            'request' => ['onRequest', 0],
        ];
    }
}