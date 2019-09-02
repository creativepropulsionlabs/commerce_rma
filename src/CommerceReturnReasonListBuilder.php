<?php

namespace Drupal\commerce_rma;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of CommerceReturnReason entities.
 */
class CommerceReturnReasonListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('CommerceReturnReason');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = $entity->getType();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
