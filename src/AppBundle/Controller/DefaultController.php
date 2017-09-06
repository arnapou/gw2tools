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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{

    /**
     *
     * @Route("/{_locale}/", requirements={"_locale" = "de|en|es|fr"}, name="index")
     */
    public function indexAction(Request $request)
    {
        return $this->render('index.html.twig');
    }

    /**
     *
     * @Route("/{_locale}/menu.html", requirements={"_locale" = "de|en|es|fr"})
     */
    public function menuAction(Request $request)
    {
        return $this->render('menu.html.twig');
    }

    /**
     *
     * @Route("/{_locale}/technical-infos", requirements={"_locale" = "de|en|es|fr"}, name="technical-infos")
     */
    public function technicalInfosAction(Request $request)
    {
        return $this->render('technical-infos.html.twig');
    }

    /**
     * redirect to the browser locale if found
     *
     * @Route("/")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function rootAction(Request $request)
    {
        $locales   = $this->getParameter('locales');
        $languages = $request->getLanguages();

        $locale = null;
        foreach ($languages as $language) {
            $lang = strtolower(substr($language, 0, 2));
            if ($lang && in_array($lang, $locales)) {
                $locale = $lang;
                break;
            }
        }

        if (empty($locale)) {
            $locale = $this->getParameter('locale');
        }

        return $this->redirect('/' . $locale . '/');
    }

    /**
     * @return string
     */
    function getViewPrefix()
    {
        return '';
    }
}
