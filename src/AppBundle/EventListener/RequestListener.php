<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListener;

use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class RequestListener
{
    /**
     *
     * @var LockHandler
     */
    private $lock;

    public function onKernelController(FilterControllerEvent $event)
    {
        $code = $event->getRequest()->attributes->get('_code');
        if ($code && 10 === \strlen($code)) {
            $this->lock = new LockHandler('gwtool.code.' . $code);
            $this->lock->lock(true);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->lock) {
            $this->lock->release();
        }
    }
}
