<?php

namespace Drupal\commerce_rma\Form;

use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommerceReturnTypeForm.
 */
class CommerceReturnTypeForm extends CommerceBundleEntityFormBase {

  use EntityDuplicateFormTrait;

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new CommerceReturnTypeForm object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager.
   */
  public function __construct(EntityTraitManagerInterface $trait_manager, WorkflowManagerInterface $workflow_manager) {
//  public function __construct(WorkflowManagerInterface $workflow_manager) {
    parent::__construct($trait_manager);

    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('plugin.manager.workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $commerce_return_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $commerce_return_type->label(),
      '#description' => $this->t("Label for the RMA type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $commerce_return_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rma\Entity\CommerceReturnType::load',
      ],
      '#disabled' => !$commerce_return_type->isNew(),
    ];

//    $storage = \Drupal::entityTypeManager()->getStorage('commerce_checkout_flow');
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_checkout_flow');
    $checkout_flows = $storage->loadMultiple();

    /** @var \Drupal\commerce_rma\Entity\CommerceReturnType $order_type */
    $order_type = $this->entity;
    $workflows = $this->workflowManager->getGroupedLabels('commerce_return');

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $order_type->getWorkflowId(),
      '#description' => $this->t('Used by all orders of this type.'),
    ];
    $form = $this->buildTraitForm($form, $form_state);

//
//    $form['commerce_checkout'] = [
//      '#type' => 'details',
//      '#title' => t('Checkout settings'),
//      '#weight' => 5,
//      '#open' => TRUE,
//    ];
//    $form['commerce_checkout']['checkout_flow'] = [
//      '#type' => 'select',
//      '#title' => t('Checkout flow'),
//      '#options' => EntityHelper::extractLabels($checkout_flows),
////      '#default_value' => $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow', 'default'),
//      '#required' => TRUE,
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
    $workflow = $this->workflowManager->createInstance($form_state->getValue('workflow'));
    // Verify "Place" transition.
    if (!$workflow->getTransition('place')) {
      $form_state->setError($form['workflow'], $this->t('The @workflow workflow does not have a "Place" transition.', [
        '@workflow' => $workflow->getLabel(),
      ]));
    }
    // Verify "draft" state.
    if (!$workflow->getState('draft')) {
      $form_state->setError($form['workflow'], $this->t('The @workflow workflow does not have a "Draft" state.', [
        '@workflow' => $workflow->getLabel(),
      ]));
    }
    $this->validateTraitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->submitTraitForm($form, $form_state);
    if ($this->operation == 'add') {
      commerce_order_add_return_items_field($this->entity);
    }

    $this->messenger()->addStatus($this->t('Saved the %label RMA type.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_return_type.collection');
  }

}
