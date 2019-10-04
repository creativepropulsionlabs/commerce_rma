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
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    // Create special routes for user zone.
    if ($add_form_route = $this->getAddFormRoute($entity_type)) {
      $add_form_route->setPath('/user/{user}/orders/{commerce_order}/returns/{commerce_return_type}/add');
      $add_form_route->setRequirement('_return_add_access', 'TRUE');
      $collection->add("entity.{$entity_type_id}.add_user_form", $add_form_route);
    }

    if ($add_page_route = $this->getAddPageRoute($entity_type)) {
      $add_page_route->setPath('/user/{user}/orders/{commerce_order}/returns/add');
      $add_page_route->setDefault('_controller',ReturnController::class . '::addReturnUserPage');
      $parameters = $add_page_route->getOption('parameters') ?: [];
      $parameters = ['user' => 'entity:user'] + $parameters;
      $add_page_route->setOption('parameters', $parameters);
      $add_page_route->setRequirement('_return_add_access', 'TRUE');

      $collection->add("entity.{$entity_type_id}.add_user_page", $add_page_route);
    }

    return $collection;
  }

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
