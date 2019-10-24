<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_rma_order_item_allow_return_status")
 */
class OrderReturnAllowed extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getEntity($values);
    if ($order->getState()->value != 'completed') {
      return '';
    }
    $order_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $return_max_order_age = $order_type->getThirdPartySetting('commerce_rma', 'return_max_order_age', 0);
    $order_max_age_timestamp = \Drupal::time()->getRequestTime() - $return_max_order_age * 86400;
    $title = $this->t('Eligible');
    $text = $this->t('This order is eligible for the return request because it is still within the 15 days limit.');
    if ($return_max_order_age && $order->getCompletedTime() < $order_max_age_timestamp) {
      $title = $this->t('Not eligible');
      $text = $this->t('This order is ineligible for the return request because it has exceeded the 15 days.');
    }

    return [
      '#title' => $title,
      '#type' => 'link',
      '#url' => Url::fromRoute('<none>'),
      '#attributes' => [
        'data-tooltip' => $text,
        'class' => ['top'],
        'tabindex' => 2,
        'title' => $text,
      ]
    ];
  }

}
