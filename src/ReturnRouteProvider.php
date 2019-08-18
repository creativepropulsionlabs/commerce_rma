<?php

namespace Drupal\commerce_rma;

use Drupal\commerce_rma\Controller\ReturnController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Return entity.
 */
class ReturnRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    if ($route) {
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
        'commerce_return_type' => [
          'type' => 'entity:commerce_return_type',
        ],
      ]);
    }
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddPageRoute($entity_type);
    if ($route) {
      $route->setDefault('_controller', ReturnController::class . '::addReturnPage');
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
      ]);
    }
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route) {
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
      ]);
      $route->setRequirement('_return_collection_access', 'TRUE');
    }
    return $route;
  }

}
