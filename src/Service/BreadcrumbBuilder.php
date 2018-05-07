<?php

namespace App\Service;

use App\Entity\Interfaces\BreabcrumbableInterface;
use App\Entity\Item;
use App\Entity\Wishlist;
use App\Model\BreadcrumbElement;

/**
 * Class BreadcrumbBuilder
 *
 * @package App\Service
 */
class BreadcrumbBuilder
{
    /**
     * @param $entity
     * @param $context
     * @param $class
     * @return array
     */
    public function build($entity, $context, $class) :array
    {
        if (!$entity instanceof BreabcrumbableInterface) {
            return [];
        }

        if ($entity instanceof Wishlist) {
            $context = 'wishlist';
        }

        $breadcrumb = [];
        $breadcrumbElement = new BreadcrumbElement();
        $breadcrumbElement->setType(BreadcrumbElement::TYPE_ENTITY)
            ->setLabel($entity->__toString())
            ->setRoute(\in_array($context, ['user', 'preview'], false) ? 'app_'.$context.'_'.$class : 'app_'.$class.'_show')
            ->setEntity($entity)
            ->setParams(['id' => $entity->getId()]);

        if ($context === 'user') {
            $breadcrumbElement->setParams(array_merge($breadcrumbElement->getParams(), ['username' => $entity->getOwner()->getUsername()]));
        }

        $breadcrumb[] = $breadcrumbElement;

        if (method_exists($entity, 'getParent') && $entity->getParent()) {
            $breadcrumb = array_merge($this->build($entity->getParent(), $context), $breadcrumb);
        }

        if ($entity instanceof Item && $entity->getCollection()) {
            $breadcrumb = array_merge($this->build($entity->getCollection(), $context, 'collection'), $breadcrumb);
        }

        return $breadcrumb;
    }
}