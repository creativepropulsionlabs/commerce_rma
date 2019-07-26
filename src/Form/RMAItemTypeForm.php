<?php

namespace Drupal\commerce_rma\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\commerce_rma\Entity\RMAType;
use Drupal\commerce_rma\Entity\RMATypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RMAItemTypeForm.
 */
class RMAItemTypeForm extends CommerceBundleEntityFormBase {

  use EntityDuplicateFormTrait;

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new RMATypeForm object.
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

    $rma_item_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rma_item_type->label(),
      '#description' => $this->t("Label for the RMA item type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rma_item_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rma\Entity\RMAItemType::load',
      ],
      '#disabled' => !$rma_item_type->isNew(),
    ];

    /** @var \Drupal\commerce_rma\Entity\RMAItemType $item_type */
    $item_type = $this->entity;
    $workflows = $this->workflowManager->getGroupedLabels('commerce_rma_item');

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $item_type->getWorkflowId(),
      '#description' => $this->t('Used by all RMA items of this type.'),
      '#required' => FALSE,
    ];
    $form = $this->buildTraitForm($form, $form_state);


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rma_item_type = $this->entity;
    $status = $rma_item_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label RMA item type.', [
          '%label' => $rma_item_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label RMA item type.', [
          '%label' => $rma_item_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($rma_item_type->toUrl('collection'));

//    $this->entity->save();
//    $this->postSave($this->entity, $this->operation);
////    $this->submitTraitForm($form, $form_state);
//    if ($this->operation == 'add') {
//      commerce_rma_add_order_item_field($this->entity);
//    }
//
//    $this->messenger()->addMessage($this->t('Saved the %label RMA Item type.', ['%label' => $this->entity->label()]));x
//    $form_state->setRedirect('entity.commerce_rma_type.collection');

  }

}
