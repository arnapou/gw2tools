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
use Symfony\Component\HttpFoundation\Response;

class RaidplannerController extends AbstractController
{

    /**
     *
     * @Route("/{_locale}/raidplanner/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function indexAction()
    {
        $context = [];
        return $this->render('index.html.twig', $context);
    }

    protected function render($view, array $parameters = [], Response $response = null)
    {
        return parent::render('raidplanner/' . $view, $parameters, $response);
    }
}
