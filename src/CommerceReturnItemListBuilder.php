<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of RMA item entities.
 *
 * @ingroup commerce_rma
 */
class CommerceReturnItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('RMA item ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\commerce_rma\Entity\CommerceReturnItem $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.commerce_return_item.edit_form',
      ['commerce_return_item' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
