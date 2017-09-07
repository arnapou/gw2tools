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

use AppBundle\Entity\RaidMember;
use AppBundle\Entity\RaidRoster;
use AppBundle\Entity\RaidWeek;
use Gw2tool\Exception\AccessNotAllowedException;
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
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidRoster::class);
            $context = $this->getContext($_code, null, true);
            $rosters = $repo->getRosters($this->token);

            if (count($rosters) == 1) {
                return $this->redirect('./detail-' . $rosters[0]['roster']->getId());
            } else {
                return $this->redirect('./list');
            }

        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/list", requirements={"_locale" = "de|en|es|fr"})
     * @param         $_code
     * @param Request $request
     * @return Response
     */
    public function listAction($_code, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidRoster::class);
            $context = $this->getContext($_code, null, true);

            $context['page_name'] = $this->trans('raidplanner');
            $context['rosters']   = $repo->getRosters($this->token);

            return $this->render('list.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/create", requirements={"_locale" = "de|en|es|fr"})
     * @param         $_code
     * @param Request $request
     * @return Response
     */
    public function createAction($_code, Request $request)
    {
        try {
            $context              = $this->getContext($_code, null, true);
            $context['page_name'] = $this->trans('raidplanner');

            try {
                if ($request->getMethod() === 'POST') {
                    $roster = new RaidRoster();
                    $roster->setCreator($this->token->getName());
                    $roster->setName($request->get('name'));
                    $roster->setDescription($request->get('description'));

                    $member = new RaidMember();
                    $member->setName($roster->getCreator());
                    $member->setRoster($roster);

                    if (empty($roster->getName())) {
                        throw new \Exception($this->trans('raidplanner.error.empty_name'));
                    }

                    $manager = $this->getDoctrine()->getManager();
                    $manager->persist($roster);
                    $manager->persist($member);
                    $manager->flush();

                    return $this->redirect('./detail-' . $roster->getId());
                }
            } catch (\Exception $ex) {
                $context['error'] = $ex->getMessage();
            }

            return $this->render('form-roster.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/modify-{id}", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function modifyAction($_code, $id, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);
            $roster  = $member->getRoster();

            $context['page_name'] = $this->trans('raidplanner');
            $context['member']    = $member;

            if (!$context['member']->canModifyRoster()) {
                return $this->redirect('./detail-' . $roster->getId());
            }

            try {
                if ($request->getMethod() === 'POST') {
                    $roster->setName($request->get('name'));
                    $roster->setDescription($request->get('description'));

                    if (empty($roster->getName())) {
                        throw new \Exception($this->trans('raidplanner.error.empty_name'));
                    }

                    $manager = $this->getDoctrine()->getManager();
                    $manager->persist($roster);
                    $manager->flush();

                    return $this->redirect('./detail-' . $roster->getId());
                }
            } catch (\Exception $ex) {
                $context['error'] = $ex->getMessage();
            }

            return $this->render('form-roster.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/delete-{id}", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function deleteAction($_code, $id, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);
            $roster  = $member->getRoster();

            $context['page_name'] = $this->trans('raidplanner');
            $context['member']    = $member;

            if (!$context['member']->canDeleteRoster()) {
                return $this->redirect('./detail-' . $roster->getId());
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->remove($roster);
            $manager->flush();

            return $this->redirect('./list');

        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }


    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/detail-{id}", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function detailAction($_code, $id, Request $request)
    {
        try {
            $repoMember = $this->getDoctrine()->getRepository(RaidMember::class);
            $repoWeek   = $this->getDoctrine()->getRepository(RaidWeek::class);
            $context    = $this->getContext($_code, null, true);

            $context['page_name'] = $this->trans('raidplanner');
            $context['member']    = $repoMember->getMember($id, $this->token);
            $context['members']   = $repoMember->getMembers($id);
            $context['date']      = $this->getDate($request);
            $context['curdate']   = $this->getDate();
            $context['weeks']     = $repoWeek->getWeeks($context['members'], $context['date']);
            $context['sums']      = $this->calcSums($context['weeks']);

            return $this->render('detail.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getDate(Request $request = null)
    {
        $date = $request ? $request->get('date') : null;
        if (!preg_match('!^(2[0-9]{3})-([0-9]{2})-([0-9]{2})$!', (string)$date, $m)) {
            $date = date('Y-m-d');
        } elseif (!checkdate($m[2], $m[3], $m[1])) {
            $date = date('Y-m-d');
        }
        $time = strtotime($date . ' 12:00:00');
        return date('Y-m-d', $time - date('N', $time) * 86400 + 86400);
    }

    /**
     * @return string
     */
    public function getViewPrefix()
    {
        return 'raidplanner/';
    }

    /**
     * @param array $weeks
     * @return array
     */
    private function calcSums(array $weeks)
    {
        $sums = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
        foreach ($weeks as $week) {
            /** @var $week RaidWeek */
            foreach ($week->getStatuses() as $index => $status) {
                $sums[$index] += $status === RaidWeek::PRESENT ? 1 : 0;
            }
        }
        return $sums;
    }
}
