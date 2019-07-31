<?php

namespace Drupal\commerce_rma\Event;

/**
 * Wraps a refund event for event listeners.
 */
final class CommerceReturnEvents {

  /**
   * Name of the event fired when payment gateways are loaded for an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_rma\Event\FilterCommerceReturnGatewaysEvent
   */
  const FILTER_RMA_GATEWAYS = 'commerce_payment.filter_rma_gateways';

}
