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

use AppBundle\Entity\RaidHistory;
use AppBundle\Entity\RaidMember;
use AppBundle\Entity\RaidRoster;
use AppBundle\Entity\RaidWeek;
use Arnapou\GW2Api\Model\Item;
use Gw2tool\Exception\AccessNotAllowedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RaidplannerController extends PageController
{
    const MAX_RAID_MEMBERS = 10;

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/", name="raidplanner",
     *     requirements={"_locale" = "de|en|es|fr"}
     *     )
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

            if (\count($rosters) == 1) {
                return $this->redirectToRosterDetail($rosters[0]['roster']);
            } else {
                return $this->redirectToRoute('raidplanner_list');
            }
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/list", name="raidplanner_list",
     *     requirements={"_locale" = "de|en|es|fr"}
     *     )
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
     * @Route("/{_locale}/{_code}/raidplanner/create", name="raidplanner_create",
     *     requirements={"_locale" = "de|en|es|fr"}
     *     )
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
                    $this->history(RaidHistory::ROSTER_CREATION, $roster, $member);
                    $manager->flush();

                    return $this->redirectToRosterDetail($roster);
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
     * @Route("/{_locale}/{_code}/raidplanner/modify-{id}", name="raidplanner_modify",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
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
            $context['members']   = $repo->getMembers($id);

            if (!$context['member']->canModifyRoster()) {
                return $this->redirectToRosterDetail($roster);
            }

            try {
                if ($request->getMethod() === 'POST') {
                    $roster->setName($request->get('name'));
                    $roster->setDescription($request->get('description'));

                    if (empty($roster->getName())) {
                        throw new \Exception($this->trans('raidplanner.error.empty_name'));
                    }

                    $manager = $this->getDoctrine()->getManager();

                    if ($member->isCreator()) {

                        // statuses
                        $statuses = $request->get('statuses');
                        $statuses = \is_array($statuses) ? $statuses : [];
                        foreach ($context['members'] as $member) {
                            $status = isset($statuses[$member->getId()]) ? $statuses[$member->getId()] : '';
                            if ($member->getStatus() != $status) {
                                $member->setStatus($status);
                                $manager->persist($member);
                                // TODO better status history management
//                                $isOfficer = $status === RaidMember::OFFICER;
//                                if ($isOfficer) {
//                                    $this->history(RaidHistory::OFFICER_PROMOTE, $roster, $member);
//                                } else {
//                                    $this->history(RaidHistory::OFFICER_RETROGRADE, $roster, $member);
//                                }
                            }
                        }

                        // names
                        $names = $request->get('names');
                        if (\is_array($names)) {
                            foreach ($context['members'] as $member) {
                                $name = $names[$member->getId()] ?? null;
                                if ($name && $member->getName() != $name) {
                                    $manager->persist($member);
                                    $this->history(RaidHistory::MEMBER_CHANGE_NAME, $roster, $member, [
                                        'old_name' => $member->getName(),
                                        'new_name' => $name,
                                    ]);
                                    $member->setName($name);
                                }
                            }
                        }
                    }

                    $manager->persist($roster);
                    $manager->flush();

                    return $this->redirectToRosterDetail($roster);
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
     * @Route("/{_locale}/{_code}/raidplanner/add-member-{id}", name="raidplanner_add_member",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
     * @Method("POST")
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function addMemberAction($_code, $id, Request $request)
    {
        $json = [];
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);

            if ($member->canAddMemberRoster()) {
                $newMemberName = trim($request->get('member'));
                if (empty($newMemberName)) {
                    throw new \Exception('raidplanner.error.empty_name');
                }

                foreach ($repo->getMembers($id) as $member) {
                    if ($member->getName() === $newMemberName) {
                        throw new \Exception('raidplanner.error.member_already_exists');
                    }
                }

                $newMember = new RaidMember();
                $newMember->setName($newMemberName);
                $newMember->setRoster($member->getRoster());

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($newMember);
                $this->history(RaidHistory::MEMBER_NEW, $newMember->getRoster(), $newMember);
                $manager->flush();

                $json['member_id'] = $newMember->getId();
            }
        } catch (\Exception $ex) {
            $this->jsonError($json, $ex);
        }
        return new JsonResponse($json);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/edit-member-{id}-{memberid}", name="raidplanner_edit_member",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+", "memberid" = "[0-9]+"}
     *     )
     * @Method("POST")
     * @param         $_code
     * @param         $id
     * @param         $memberid
     * @param Request $request
     * @return Response
     */
    public function editMemberAction($_code, $id, $memberid, Request $request)
    {
        $json = [];
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);

            $memberToEdit = $repo->find($memberid);

            if ($memberToEdit && $member->canEditMember($memberToEdit)) {
                $memberToEdit->setText((string)$request->get('text'));

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($memberToEdit);
                $manager->flush();

                $json['member_id'] = $memberToEdit->getId();
            }
        } catch (\Exception $ex) {
            $this->jsonError($json, $ex);
        }
        return new JsonResponse($json);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/edit-day-{id}-{weekid}-{index}", name="raidplanner_edit_day",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+", "weekid" = "[0-9]+", "index" = "[1-7]"}
     *     )
     * @Method("POST")
     * @param         $_code
     * @param         $id
     * @param         $weekid
     * @param         $index
     * @param Request $request
     * @return Response
     */
    public function editDayAction($_code, $id, $weekid, $index, Request $request)
    {
        $json = [];
        try {
            $repoMember = $this->getDoctrine()->getRepository(RaidMember::class);
            $repoWeek   = $this->getDoctrine()->getRepository(RaidWeek::class);
            $context    = $this->getContext($_code, null, true);
            $member     = $repoMember->getMember($id, $this->token);
            $week       = $repoWeek->find($weekid);

            if ($member->canModifyDay($week)) {
                $week->setText($index, (string)$request->get('text'));

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($week);
                $manager->flush();

                $json['week_id'] = $week->getId();
            }
        } catch (\Exception $ex) {
            $this->jsonError($json, $ex);
        }
        return new JsonResponse($json);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/delete-{id}", name="raidplanner_delete",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
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

            if (!$member->canDeleteRoster()) {
                return $this->redirectToRosterDetail($roster);
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->remove($roster);
            $manager->flush();

            return $this->redirectToRoute('raidplanner_list');
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/leave-{id}", name="raidplanner_leave",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function leaveAction($_code, $id, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);
            $roster  = $member->getRoster();

            if (!$member->canLeaveRoster()) {
                return $this->redirectToRosterDetail($roster);
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->remove($member);
            $this->history(RaidHistory::MEMBER_LEAVE, $roster, $member);
            $manager->flush();

            return $this->redirectToRoute('raidplanner_list');
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/remove-{id}-{memberid}", name="raidplanner_remove",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+", "memberid" = "[0-9]+"}
     *     )
     * @param         $_code
     * @param         $id
     * @param         $memberid
     * @param Request $request
     * @return Response
     */
    public function removeAction($_code, $id, $memberid, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $member  = $repo->getMember($id, $this->token);
            $roster  = $member->getRoster();

            $memberToRemove = $repo->find($memberid);

            if (!empty($memberToRemove) && $member->canRemoveMember($memberToRemove)) {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($memberToRemove);
                $this->history(RaidHistory::MEMBER_REMOVE, $roster, $member, ['removed' => $memberToRemove->getName()]);
                $manager->flush();
            }

            return $this->redirectToRosterDetail($roster);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/detail-{id}", name="raidplanner_detail",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
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

            $context['page_name']   = $this->trans('raidplanner');
            $context['member']      = $repoMember->getMember($id, $this->token);
            $context['members']     = $repoMember->getMembers($id);
            $context['date']        = $this->getDate($request);
            $context['curdate']     = $this->getDate();
            $context['weeks']       = $repoWeek->getWeeks($context['members'], $context['date']);
            $context['sums']        = $this->calcSums($context['weeks']);
            $context['statuses']    = RaidWeek::getStatusList();
            $context['timedays']    = $this->getTimeDays($context['date']);
            $context['weekcreator'] = $this->getWeekCreator($context['weeks']);

            if ($context['member']->checkData($this->account)) {
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($context['member']);
                $manager->flush();
            }

            return $this->render('detail.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/characters-{id}-{memberid}", name="raidplanner_characters",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+", "memberid" = "[0-9]+"}
     *     )
     * @param         $_code
     * @param         $id
     * @param         $memberid
     * @param Request $request
     * @return Response
     */
    public function charactersAction($_code, $id, $memberid, Request $request)
    {
        try {
            $repo    = $this->getDoctrine()->getRepository(RaidMember::class);
            $context = $this->getContext($_code, null, true);
            $me      = $repo->getMember($id, $this->token);
            $member  = $repo->find($memberid);

            if ($me->getRoster()->getId() != $member->getRoster()->getId()) {
                return $this->redirectToRosterDetail($me->getRoster());
            }

            $env      = $this->getGwEnvironment();
            $data     = $member->getData();
            $upgrades = [];
            if (isset($data['characters'])) {
                foreach ($data['characters'] as $char) {
                    foreach ($char['blocks'] as $block) {
                        foreach ($block['upgrades'] as $id => $qty) {
                            $upgrades[$id] = new Item($env, $id);
                        }
                    }
                }
            }

            $context['page_name'] = $this->trans('raidplanner');
            $context['member']    = $member;
            $context['upgrades']  = $upgrades;

            return $this->render('characters.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/history-{id}", name="raidplanner_history",
     *     requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"}
     *     )
     * @param         $_code
     * @param         $id
     * @param Request $request
     * @return Response
     */
    public function historyAction($_code, $id, Request $request)
    {
        try {
            $repoMember  = $this->getDoctrine()->getRepository(RaidMember::class);
            $repoHistory = $this->getDoctrine()->getRepository(RaidHistory::class);
            $context     = $this->getContext($_code, null, true);
            $member      = $repoMember->getMember($id, $this->token);
            $page        = $request->get('page');
            $pagination  = $repoHistory->getPagination($member->getRoster(), $page);

            $context['page_name']  = $this->trans('raidplanner');
            $context['member']     = $member;
            $context['history']    = $repoHistory->getHistory($member->getRoster(), $page);
            $context['pagination'] = $pagination;

            return $this->render('history.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/raidplanner/save-date", name="raidplanner_save_date",
     *     requirements={"_locale" = "de|en|es|fr"}
     *     )
     * @Method("POST")
     * @param         $_code
     * @param Request $request
     * @return JsonResponse
     */
    public function saveDateAction($_code, Request $request)
    {
        $json = [];
        try {
            $id     = (string)$request->get('roster');
            $status = $request->get('status');
            $weekid = (string)$request->get('weekid');
            $index  = (string)$request->get('index');

            if (!\ctype_digit($id)) {
                throw new \Exception('roster parameter is not an id.');
            }
            if (!\ctype_digit($weekid)) {
                throw new \Exception('weekid parameter is not an id.');
            }
            if (!\ctype_digit($index) || $index < 1 || $index > 7) {
                throw new \Exception('index parameter is not a valid integer.');
            }
            if (!\in_array($status, RaidWeek::getStatusList())) {
                throw new \Exception('status parameter is not valid.');
            }

            $repoMember = $this->getDoctrine()->getRepository(RaidMember::class);
            $repoWeek   = $this->getDoctrine()->getRepository(RaidWeek::class);
            $context    = $this->getContext($_code, null, true);

            $member  = $repoMember->getMember($id, $this->token);
            $members = $repoMember->getMembers($id);
            $week    = $repoWeek->find($weekid);
            $weeks   = $repoWeek->getWeeks($members, $week->getDate());
            $sums    = $this->calcSums($weeks);

            if (!$member->canModifyWeek($week)) {
                throw new \Exception('you have not the right to modify this week.');
            }
            if ($sums[$index] >= self::MAX_RAID_MEMBERS && $status === RaidWeek::PRESENT) {
                $status = RaidWeek::BACKUP;
            }

            $week->setStatus($index, $status);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($week);
            $this->history(RaidHistory::STATUS_CHANGE, $member->getRoster(), $member, [
                'date'   => date('Y-m-d', strtotime($week->getDate() . ' 12:00:00') + 86400 * ($index - 1)),
                'status' => $status,
                'target' => $week->getMember()->getName(),
            ]);
            $manager->flush();

            $json['sum']    = $this->calcSums($weeks)[$index];
            $json['status'] = $status;
        } catch (\Exception $ex) {
            $this->jsonError($json, $ex);
        }
        return new JsonResponse($json);
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

    protected function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        if (!isset($parameters['_code']) && $this->token) {
            $parameters['_code'] = $this->token->getCode();
        }
        return parent::redirectToRoute($route, $parameters, $status);
    }

    private function jsonError(&$json, \Exception $exception)
    {
        if (\strpos($exception->getMessage(), 'raidplanner.error.') === 0) {
            $json['error'] = $this->trans($exception->getMessage());
        } else {
            $json['error'] = $this->trans('raidplanner.error.generic');
        }

        if ($this->getEnv() === 'dev') {
            $json['exception'] = [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ];
        }
    }

    /**
     * @param            $type
     * @param RaidRoster $roster
     * @param RaidMember $member
     * @param array      $data
     */
    private function history($type, RaidRoster $roster, RaidMember $member = null, $data = [])
    {
        $history = new RaidHistory();
        $history->setRoster($roster);
        $history->setType($type);
        $history->setMemberName($member ? $member->getName() : '');
        $history->setData($data);
        $this->getDoctrine()->getManager()->persist($history);
    }

    /**
     * @param RaidRoster $roster
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function redirectToRosterDetail(RaidRoster $roster)
    {
        return $this->redirectToRoute('raidplanner_detail', ['id' => $roster->getId()]);
    }

    /**
     * @param $date
     * @return array
     */
    private function getTimeDays($date)
    {
        $time  = \strtotime($date . ' 12:00:00');
        $times = [];
        for ($i = 1; $i <= 7; $i++) {
            $times[$i] = $time;
            $time      += 86400;
        }
        return $times;
    }

    /**
     * @param RaidWeek[] $weeks
     * @return RaidWeek
     */
    private function getWeekCreator($weeks)
    {
        foreach ($weeks as $week) {
            if ($week->getMember()->isCreator()) {
                return $week;
            }
        }
        return null;
    }
}
