<?php

namespace Drupal\commerce_rma\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
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
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

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
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_return.approve.post_transition' => ['returnOrder', -100],
      'commerce_return.reject.post_transition' => ['mapReturnStateToOrder', -100],
      'commerce_return.complete.post_transition' => ['completeOrder', -100],
      'commerce_return.cancel.post_transition' => ['mapReturnStateToOrder', -100],
    ];
    return $events;
  }

  /**
   * Return an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function mapReturnStateToOrder(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $return = $event->getEntity();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $return->getOrder();
    $return_state = $return->getState();
    $order_transition_id = $this->getOrderStateId($return_state);
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $order_return_workflow_id = $order_type->getThirdPartySetting('commerce_rma', 'return_workflow');
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_workflow */
    $order_return_workflow = $this->workflowManager->createInstance($order_return_workflow_id);
    $transition = $order_return_workflow->getTransition($order_transition_id);
    if ($order->get('return_state')->isEmpty()) {
      $order->return_state = 'draft';
      $order->save();
    }
    $order->get('return_state')->first()->applyTransition($transition);
    $order->save();
  }

  /**
   * Return an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function completeOrder(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $return = $event->getEntity();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $return->getOrder();

    $return_state = $return->getState();
    $order_transition_id = $this->isOrderFullReturned($order);

    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $order_return_workflow_id = $order_type->getThirdPartySetting('commerce_rma', 'return_workflow');

    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_workflow */
    $order_return_workflow = $this->workflowManager->createInstance($order_return_workflow_id);
    $transition = $order_return_workflow->getTransition($order_transition_id);
    if ($order->get('return_state')->isEmpty()) {
      $order->return_state = 'draft';
      $order->save();
    }
    $order->get('return_state')->first()->applyTransition($transition);
    $order->save();
  }


  protected function getOrderStateId($return_sate) {
    $map = [
      'rejected' => 'reject',
      'completed' => 'complete',
      'canceled' => 'cancel',
    ];
    return $map[$return_sate->getId()];
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
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface[] $returns */
    $returns = $order->get('returns')->referencedEntities();
    foreach ($returns as $return) {
      if ($return->get('confirmed_total_quantity')->isEmpty()) {
        continue;
      }
      $quantity_confirmed = $return->get('confirmed_total_quantity')->value;
      $order_total_quantity_confirmed = Calculator::add($order_total_quantity_confirmed, $quantity_confirmed);
    }
    if (Calculator::compare($order_total_quantity, $order_total_quantity_confirmed) == 1 ) {
      $transition_id = 'partial_return';
    }
    if ($order->getState()->value == $transition_id) {
      return;
    }
//    elseif ($order->getState()->value == 'partial_returned') {
//      $transition_id = 'partial_return_returned';
//    }

    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $order_workflow_id = $order_type->getThirdPartySetting('commerce_rma', 'return_workflow');
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_workflow */
    $order_workflow = $this->workflowManager->createInstance($order_workflow_id);
    $transition = $order_workflow->getTransition($transition_id);
    if ($order->get('return_state')->isEmpty()) {
      $order->return_state = 'draft';
      $order->save();
    }
    if (!$transition) {
      return;
    }
    $order->get('return_state')->first()->applyTransition($transition);
    $order->save();
  }

  protected function isOrderFullReturned(OrderInterface $order) {
    $order_transition_id = 'partial_return';
    $order_items = $order->getItems();
    $accepted_states = [
      'approved',
      'completed',
    ];
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface[] $returns */
    $returns = $order->get('returns')->referencedEntities();
    foreach ($order_items as $order_item) {
      $order_item_quantity = $order_item->getQuantity();
      foreach ($returns as $return) {
        if (!in_array($return->getState()->value, $accepted_states)) {
          continue;
        }
        $return_items = $return->getItems();
        foreach ($return_items as $return_item){
          if ($return_item->getOrderItem()->id() == $order_item->id()){
            $order_item_quantity = \CommerceGuys\Intl\Calculator::subtract($order_item_quantity, $return_item->getConfirmedTotalQuantity());
          }
        }
      }
      $order_item_quantities[] = $order_item_quantity;
    }
    $order_item_quantities = array_filter($order_item_quantities);
    if (empty($order_item_quantities)) {
      $order_transition_id = 'complete';
    }

    return $order_transition_id;
  }

}
