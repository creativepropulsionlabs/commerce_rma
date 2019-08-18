<?php

namespace Drupal\commerce_rma\Guard;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\state_machine\Guard\BaseGuard;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\state_machine\WorkflowManagerInterface;
use Drupal\user\UserInterface;

class CommerceReturnGuard implements GuardInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new PublicationGuard object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user..
   */
  public function __construct(AccountProxyInterface $current_user, WorkflowManagerInterface $workflow_manager) {
    $this->currentUser = $current_user;
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    // Don't allow transition for users without permissions.
    if (!$this->currentUser->hasPermission('access ' . $entity->bundle() . ' ' . $transition->getId() . ' returns')) {
      return FALSE;
    }
  }

}
