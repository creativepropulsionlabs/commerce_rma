<?php

namespace Drupal\commerce_rma\Plugin\views\field;

use Drupal\commerce_rma\Entity\CommerceReturnReasonInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for editing the order item quantity.
 *
 * @ViewsField("commerce_rma_order_item_edit_reason")
 */
class CommerceReturnEditReason extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EditQuantity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['allow_decimal'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['allow_decimal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow decimal quantities'),
      '#default_value' => $this->options['allow_decimal'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }

    // Make options for reason field in view.
    $options = [];
    /** @var CommerceReturnReasonInterface[] $reasons */
    $reasons = $this->entityTypeManager->getStorage('commerce_return_reason')->loadMultiple();
    foreach ($reasons as $reason) {
      $options[$reason->id()] = $reason->label();
    }

    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {

      $form[$this->options['id']][$row_index] = [
        '#type' => 'select',
        '#title' => $this->t('Reason'),
        '#title_display' => 'invisible',
        '#empty_option' =>$this->t('Please select'),
        '#options' => $options,
//        '#required' => FALSE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $reasons = $form_state->getValue($this->options['id']);

    // Find - if we connect handler for RMA quantity field.
    $handlers = $this->view->getHandlers('field');

    foreach ($handlers as $handler) {
      if ($handler['plugin_id'] == 'commerce_rma_order_item_edit_quantity') {
        $quantity_handler = $handler;
        break;
      }
    }

    foreach ($reasons as $row_index => $reason) {
      if (empty($reason)) {
        $quantity = isset($quantity_handler) ? $form_state->getValue($quantity_handler['id'])[$row_index] : NULL;
        if ($quantity >= 0) {
          $form_state->setErrorByName('', $this->emptySelectedMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No reason selected.');
  }

  /**
   * Submit handler for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    // Do nothing. All logic in CommerceReturnEditQuantity.
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

}
