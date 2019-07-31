<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of RMA entities.
 *
 * @ingroup commerce_rma
 */
class CommerceReturnListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Return ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\commerce_rma\Entity\CommerceReturn $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.commerce_return.edit_form',
      ['commerce_return' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
