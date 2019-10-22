<?php


namespace Drupal\commerce_rma\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfirmTransitionForm extends ConfirmFormBase {

  /**
   * Workflow.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface
   */
  protected $workflow;

  /**
   * Transition.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition
   */
  protected $transition;

  /**
   * Transition Entity applied to.
   *
   * @var \Drupal\commerce\Plugin\Commerce\InlineForm\ContentEntity
   */
  protected $entity;

  /**
   * The entity type manager prophecy used in the test.
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
   * ConfirmTransitionForm constructor.
   * @param \Drupal\state_machine\Plugin\Workflow\Workflow $workflow
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowTransition $transition
   * @param \Drupal\commerce\Plugin\Commerce\InlineForm\ContentEntity $entity
   */
  public function __construct(WorkflowManagerInterface $workflow_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->workflowManager = $workflow_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workflow'),
      $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $commerce_return = NULL, $workflow = NULL, $workflow_transition = NULL) {
    $this->workflow = $this->workflowManager->createInstance($workflow);
    $this->transition = $this->workflow->getTransition($workflow_transition);
    $this->entity = $this->entityTypeManager->getStorage('commerce_return')->load($commerce_return);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->getState()->applyTransition($this->transition);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "confirm_transition_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Back');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $return_id = $this->getRequest()->query->get('commerce_return');
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $return = $this->entityTypeManager->getStorage('commerce_rreturn')->load($return_id);

    return new Url('entity.commerce_return.collection', [
      'commerce_order' => $return->getOrder()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    //@todo replace this if with one for regular user.
    if ($this->transition->getId() == 'cancel') {
      return t('Are you sure you want to %transition for order # %order_id?',
        [
          '%order_id' => $this->entity->getOrderId(),
          '%transition' => $this->transition->getLabel()
        ]);
    }

    return t('Are you sure you want to %transition # %return_id?',
      [
        '%return_id' => $this->entity->id(),
        '%transition' => $this->transition->getLabel()
      ]);
  }

}
