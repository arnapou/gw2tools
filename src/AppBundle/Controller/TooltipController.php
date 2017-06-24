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

use Arnapou\GW2Api\Model\Item;
use Arnapou\GW2Api\Model\InventorySlot;
use Arnapou\GW2Api\Model\Pet;
use Arnapou\GW2Api\Model\PvpAmulet;
use Arnapou\GW2Api\Model\Skin;
use Arnapou\GW2Api\Model\Skill;
use Arnapou\GW2Api\Model\Specialization;
use Arnapou\GW2Api\Model\SpecializationTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TooltipController extends AbstractController
{

    /**
     * 
     * @Route("/{_locale}/tooltip/trait-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipTraitAction($id, Request $request)
    {
        return $this->renderTooltip('trait', function() use($id) {
                return [
                    'trait' => new SpecializationTrait($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/pet-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipPetAction($id, Request $request)
    {
        return $this->renderTooltip('pet', function() use($id) {
                return [
                    'pet' => new Pet($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/pvpamulet-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipPvpAmuletAction($id, Request $request)
    {
        return $this->renderTooltip('pvpamulet', function() use($id) {
                return [
                    'pvpamulet' => new PvpAmulet($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/skill-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipSkillAction($id, Request $request)
    {
        return $this->renderTooltip('skill', function() use($id) {
                return [
                    'skill' => new Skill($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/specialization-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipSpecializationAction($id, Request $request)
    {
        return $this->renderTooltip('specialization', function() use($id) {
                return [
                    'specialization' => new Specialization($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/skin-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipSkinAction($id, Request $request)
    {
        return $this->renderTooltip('skin', function() use($id) {
                return [
                    'skin' => new Skin($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/item-{id}.html", requirements={"_locale" = "de|en|es|fr", "id" = "[0-9]+"})
     */
    public function tooltipItemAction($id, Request $request)
    {
        return $this->renderTooltip('item', function() use($id) {
                return [
                    'item' => new Item($this->getGwEnvironment(), $id),
                ];
            });
    }

    /**
     * 
     * @Route("/{_locale}/tooltip/slot-{code}.html", requirements={"_locale" = "de|en|es|fr", "code" = "[0-9]+(-((up|in|sk|cn|ch|st|z[a-z])[0-9]+|(bn|bt).+))*"})
     */
    public function tooltipSlotAction($code, Request $request)
    {
        $data = [];
        foreach (explode('-', $code) as $i => $s) {
            if ($i == 0) {
                $data['id'] = $s;
            } else {
                $k    = substr($s, 0, 2);
                $v    = substr($s, 2);
                $map1 = [
                    'za' => 'AgonyResistance',
                    'zb' => 'BoonDuration',
                    'zc' => 'ConditionDamage',
                    'zd' => 'ConditionDuration',
                    'ze' => 'CritDamage',
                    'zf' => 'Healing',
                    'zg' => 'Power',
                    'zh' => 'Precision',
                    'zi' => 'Toughness',
                    'zj' => 'Vitality',
                ];
                $map2 = [
                    'up' => 'upgrades',
                    'in' => 'infusions',
                ];
                $map3 = [
                    'ch' => 'charges',
                    'cn' => 'count',
                    'sk' => 'skin',
                    'bn' => 'binding',
                    'bt' => 'bound_to',
                ];
                if ($k === 'st') {
                    $data['stats']['id'] = $v;
                } elseif (isset($map1[$k])) {
                    $data['stats']['attributes'][$map1[$k]] = $v;
                } elseif (isset($map2[$k])) {
                    $data[$map2[$k]][] = $v;
                } elseif (isset($map3[$k])) {
                    $data[$map3[$k]] = $v;
                }
            }
        }
        return $this->renderTooltip('item', function() use($data) {
                return [
                    'item' => new InventorySlot($this->getGwEnvironment(), $data),
                ];
            });
    }

    /**
     * 
     * @param string $page
     * @param callable $context
     * @return Response
     */
    protected function renderTooltip($page, $context)
    {
        try {
            $response = $this->render('tooltip-' . $page . '.html.twig', $context());
            $response->setMaxAge(900);
            $response->setExpires(new \DateTime('@' . (time() + 900)));
            $response->setPublic();
            return $response;
        } catch (\Exception $e) {
            $html = '<div class="gwitemerror">Error</div>'
                . '<script type="text/javascript">'
                . 'if(typeof(console) != "undefined") {'
                . 'console.error(' . json_encode($e->getMessage()) . ');'
                . '}'
                . '</script>';
            return new Response($html);
        }
    }
}
