<?php

namespace Drupal\commerce_rma\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering the available payment gateways.
 *
 * @see \Drupal\commerce_payment\Event\PaymentEvents
 */
class FilterCommerceReturnGatewaysEvent extends Event {

  /**
   * The payment gateways.
   *
   * @var \Drupal\commerce_payment\Entity\RMAGatewayInterface[]
   */
  protected $refundGateways;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new FilterPaymentGatewaysEvent object.
   *
   * @param \Drupal\commerce_rma\Entity\RefundGatewayInterface[] $refund_gateways
   *   The payment gateways.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(array $refund_gateways, OrderInterface $order) {
    $this->refundGateways = $refund_gateways;
    $this->order = $order;
  }

  /**
   * Gets the payment gateways.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface[]
   *   The payment gateways.
   */
  public function getRefundGateways() {
    return $this->refundGateways;
  }

  /**
   * Sets the payment gateways.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways
   *   The payment gateways.
   *
   * @return $this
   */
  public function setRefundGateways(array $refund_gateways) {
    $this->refundGateways = $refund_gateways;
    return $this;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

}
