<?php

namespace Drupal\commerce_rma\Plugin\Field\FieldWidget;

use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\workflows\WorkflowInterface;

/**
 * Plugin implementation of 'rma_commerce_billing_profile'.
 *
 * @FieldWidget(
 *   id = "rma_transitions_links",
 *   label = @Translation("RMA Transition links"),
 *   field_types = {
 *     "state"
 *   }
 * )
 */
class RmaTransitionLinksWidget extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $entity = $items[$delta]->getEntity();
    $operations = [];
    /** @var OrderTypeInterface $order_type */
    $return_type = \Drupal::entityTypeManager()
      ->getStorage('commerce_return_type')
      ->load($entity->bundle());
    $workflow_id = $return_type->getWorkflowId();
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    /** @var WorkflowInterface $workflow */
    $workflow = $workflow_manager->createInstance($workflow_id);
    if ($workflow) {
      /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[] $transitions */
      $transitions = $workflow->getAllowedTransitions($entity->getState()->value, $entity);
      $destination = \Drupal::destination()->get();
      foreach ($transitions as $transition) {
        $operations[$transition->getId()] = [
          'title' => $transition->getLabel(),
          'url' => Url::fromRoute('commerce_rma.confirm_transition', [
            'commerce_return' => $entity->id(),
            'workflow' => $workflow_id,
            'workflow_transition' => $transition->getId()
          ], [
            'query' => ['destination' => $destination],
//            'absolute' => TRUE,
          ]),
          'weight' => -1,
        ];
      }
    }

    $element = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    return $element;
  }
}