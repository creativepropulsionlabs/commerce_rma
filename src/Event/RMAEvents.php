<?php

namespace Drupal\commerce_rma\Event;

final class RMAEvents {

  /**
   * Name of the event fired when payment gateways are loaded for an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent
   */
  const FILTER_RMA_GATEWAYS = 'commerce_payment.filter_rma_gateways';

}