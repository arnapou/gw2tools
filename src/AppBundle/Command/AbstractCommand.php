<?php
/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace AppBundle\Command;

use Arnapou\GW2Api\Environment;
use Arnapou\GW2Api\Model\AbstractStoredObject;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractCommand extends \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand
{

    use \Gw2tool\Gw2ApiEnvironmentTrait;

    /**
     * 
     * @return RegistryInterface
     */
    public function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * 
     * @return array
     */
    public function getLocales()
    {
        return $this->getContainer()->getParameter('locales');
    }

    /**
     * 
     * @param Environment $env
     * @param string $lang
     * @return array
     */
    protected function getArrayClasses(Environment $env, $lang)
    {
        $array = [];
        foreach ($this->getAbstractStoredObjectClasses($env) as $class) {
            try {
                $obj     = new $class($env, 0); /* @var $obj AbstractStoredObject */
                $array[] = [
                    'method' => $obj->getApiMethod(),
                    'name'   => $obj->getApiName(),
                    'lang'   => $lang,
                    'time'   => 86400,
                ];
            } catch (\Exception $ex) {
                
            }
        }
        $array[] = [
            'method' => 'apiCommercePrices',
            'name'   => 'prices',
            'lang'   => 'en',
            'time'   => 600,
        ];
        return $array;
    }

    /**
     * 
     * @param Environment $env
     * @return array
     */
    private function getAbstractStoredObjectClasses(Environment $env)
    {

        $folder = __DIR__ . '/../../../vendor/arnapou/gw2apiclient/src/Arnapou/GW2Api/Model';
        if (!is_dir($folder)) {
            throw new \Exception('Unable to find the GW2Api model folder.');
        }

        $files = glob($folder . '/*.php');
        if (!is_array($files)) {
            $files = [];
        }

        $classes = [];
        foreach ($files as $file) {
            $basename = basename($file, '.php');
            if (stripos($basename, 'Abstract') !== false) {
                continue;
            }
            try {
                $class          = "Arnapou\\GW2Api\\Model\\" . $basename;
                $parents        = class_parents($class);
                $isStorageClass = false;
                foreach ($parents as $parent) {
                    if ($parent == AbstractStoredObject::class) {
                        $isStorageClass = true;
                        break;
                    }
                }
                if ($isStorageClass) {
                    $classes[] = $class;
                }
            } catch (\Exception $ex) {
                
            }
        }
        return $classes;
    }
}
