<?php

/*
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2017, Fiare Oy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\CategoryTree;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Category;
use Gedmo\Sluggable\Util as Sluggable;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This fixture is used to load category data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Fiare Oy
 *
 * @version v1.0
 */
class LoadCategoryData extends Fixture implements OrderedFixtureInterface
{

    /**
     * Load fixture.
     *
     * @param ObjectManager $em object
     */
    public function load(ObjectManager $em)
    {
        $metadata = $em->getClassMetaData('App\Entity\CategoryTree');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);


        $totalLevel = 6;
        $id = 1;
        $rowCount = 1;
        $categoryObjArray = [];

        $level0 = new CategoryTree();
//        $level0->setId($id++);
        $level0->setName('All');
        $em->persist($level0);
        $em->flush($level0);


        // check whether file is present
        $path = __DIR__.'/categories.csv';

        if (false !== ($reader = new \EasyCSV\Reader($path))) {
            $reader->setDelimiter(',');
            while ($row = $reader->getRow())
            {
                for ($level = 1; $level <= $totalLevel; ++$level) {
                    $categoryIndentifier = null;
                    $parentCategoryIndentifier = null;

                    for ($i = 1; $i <= $level; ++$i) {
                        $categoryIndentifier .= $row['Level' . $i];
                    }

                    for ($j = 1; $j < $level; ++$j) {
                        $parentCategoryIndentifier .= $row['Level' . $j];
                    }

                    if ('' != $row['Level' . $level] && !array_key_exists(md5($categoryIndentifier), $categoryObjArray)) {
                        $categoryObj = 'category_' . $rowCount . '_' . $level;

                        if (!isset(${$categoryObj})) {
                            ${$categoryObj} = new CategoryTree();
                            ${$categoryObj}->setName($row['name_en' ]);

                            if (array_key_exists(md5($parentCategoryIndentifier), $categoryObjArray)) {
                                ${$categoryObj}->setParent(${$categoryObjArray[md5($parentCategoryIndentifier)]});
                            } else {
                                ${$categoryObj}->setParent($level0);
                            }

                            $em->persist(${$categoryObj});
                            $em->flush();
                            echo ".";

                            ++$id;
                            $categoryObjArray[md5($categoryIndentifier)] = $categoryObj;
                        }
                    }
                }

                ++$rowCount;
            }

            $em->flush();
            $em->clear();
        }
    }
    /**
     * Get order of fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}
