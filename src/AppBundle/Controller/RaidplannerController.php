<?php
/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RaidplannerController extends PageController
{

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/", requirements={"_locale" = "de|en|es|fr"})
     * @param         $_code
     * @param Request $request
     * @return Response
     */
    public function indexAction($_code, Request $request)
    {
        $context = $this->getContext($_code, null, true);
        return $this->render('index.html.twig', $context);
    }

    /**
     * @return string
     */
    function getViewPrefix()
    {
        return 'raidplanner/';
    }
}
