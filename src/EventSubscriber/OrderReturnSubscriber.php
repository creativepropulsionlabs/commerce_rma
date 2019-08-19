<?php

namespace Drupal\commerce_rma\EventSubscriber;

use Drupal\commerce_order\Mail\OrderReceiptMailInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderReturnSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order\Mail\OrderReceiptMailInterface $order_receipt_mail
   *   The mail handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkflowManagerInterface $workflow_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_return.place.post_transition' => ['returnOrder', -100],
    ];
    return $events;
  }

  /**
   * Return an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function returnOrder(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $return = $event->getEntity();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $return->getOrder();
    $transition_id = 'return';
    $order_total_quantity = '0';
    foreach ($order->getItems() as $order_item) {
      $order_total_quantity = Calculator::add($order_total_quantity, $order_item->getQuantity());
    }

    $order_total_quantity_confirmed = '0';
    $returns = $order->get('returns')->referencedEntities();
    foreach ($returns as $return) {
      if ($return->get('confirmed_quantity')->isEmpty()) {
        continue;
      }
      $quantity_confirmed = $return->get('confirmed_quantity')->value;
      $order_total_quantity_confirmed = Calculator::add($order_total_quantity_confirmed, $quantity_confirmed);
    }
    if (Calculator::compare($order_total_quantity, $order_total_quantity_confirmed) == 1 ) {
      $transition_id = 'partial_return';
    }
    if ($order->getState()->value == 'partial_returned' && $transition_id == 'partial_return') {
      $transition_id = 'partial_return_partial_return';
    }
    elseif ($order->getState()->value == 'partial_returned') {
      $transition_id = 'partial_return_returned';
    }

    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $order_workflow_id = $order_type->getWorkflowId();
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_workflow */
    $order_workflow = $this->workflowManager->createInstance($order_workflow_id);
    $transition = $order_workflow->getTransition($transition_id);
    $order->getState()->applyTransition($transition);
    $order->save();
  }

}
