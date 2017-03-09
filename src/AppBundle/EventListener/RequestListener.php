<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Filesystem\LockHandler;

class RequestListener {

    /**
     *
     * @var LockHandler
     */
    private $lock;

    public function onKernelController(FilterControllerEvent $event) {
        $code = $event->getRequest()->attributes->get('_code');
        if ($code && 10 === strlen($code)) {
            $this->lock = new LockHandler('gwtool.code.' . $code);
            $this->lock->lock(true);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        if ($this->lock) {
            $this->lock->release();
        }
    }

}
