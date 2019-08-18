<?php

namespace Drupal\commerce_rma;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PermissionController implements ContainerInjectionInterface {
  use StringTranslationTrait;
  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AutoEntityLabelPermissionController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('entity_type.manager'));
  }

  /**
   * Returns an array of per return type km-settings permissions
   *
   * @return array
   */
  public function StatePermissions() {
    $permissions = [];
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnTypeInterface[] $return_types */
    $return_types = $this->entityTypeManager->getStorage('commerce_return_type')
      ->loadMultiple();

    foreach ($return_types as $return_type_id => $return_type) {
      $workflow_id = $return_type->getWorkflowId();
      /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
      $workflow_manager = \Drupal::service('plugin.manager.workflow');
      /** @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow */
      $workflow = $workflow_manager->createInstance($workflow_id);
      $states = $workflow->getStates();
      foreach ($states as $state) {
        $permissions['access ' . $return_type_id . ' ' . $state->getId() . ' returns'] = [
          'title' => $this->t('%return_type_label: Access returns in state %state_label [%state_id]', [
            '%return_type_label' => $return_type->label(),
            '%state_label' => $state->getLabel(),
            '%state_id' => $state->getId()
          ]),
          'restrict access' => TRUE,
        ];
      }
    }
    return $permissions;
  }

  /**
   * Returns an array of per order type km-settings permissions
   *
   * @return array
   */
  public function TransitionPermissions() {
    $permissions = [];
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnTypeInterface[] $return_types */
    $return_types = $this->entityTypeManager->getStorage('commerce_return_type')
      ->loadMultiple();

    foreach ($return_types as $return_type_id => $return_type) {
      $workflow_id = $return_type->getWorkflowId();
      /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
      $workflow_manager = \Drupal::service('plugin.manager.workflow');
      /** @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow */
      $workflow = $workflow_manager->createInstance($workflow_id);
      $transitions = $workflow->getTransitions();
      foreach ($transitions as $transition) {
        $permissions['access ' . $return_type_id . ' ' . $transition->getId() . ' returns'] = [
          'title' => $this->t('%return_type_label: Access returns transition %transition_label [%transition_id]', [
            '%return_type_label' => $return_type->label(),
            '%transition_label' => $transition->getLabel(),
            '%transition_id' => $transition->getId()
          ]),
          'restrict access' => TRUE,
        ];
      }
    }
    return $permissions;
  }

}
