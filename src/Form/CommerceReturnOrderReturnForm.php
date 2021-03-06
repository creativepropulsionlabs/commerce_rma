<?php

namespace Drupal\commerce_rma\Form;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Form\CustomerFormTrait;
use Drupal\commerce_order\Mail\OrderReceiptMailInterface;
use Drupal\commerce_order\OrderAssignmentInterface;
use Drupal\commerce_rma\Entity\CommerceReturn;
use Drupal\commerce_rma\Entity\CommerceReturnItem;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a confirmation form for returning order.
 */
class CommerceReturnOrderReturnForm extends FormBase {

    use CustomerFormTrait;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new CommerceReturnOrderReturnForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */

  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
//    $this->entity = $this->order;
//    $this->orderAssignment = $order_assignment;
//    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_rma_return_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to return the order %label?', [
      '%label' => $this->order->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Order return.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
//    return $this->entity->toUrl('collection');
    return $this->order->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label1'] = [
      '#markup' => $this->t('Are you sure you want to return the order @label?', [
        '@label' => $this->order->label(),
      ]),
    ];

    $form['view'] = [
      '#type' => 'view',
      '#name' => 'commerce_rma_order',
      '#display_id' => 'commerce_rma_order_items',
      '#arguments' => [$this->order->id()],
    ];

//    $form['actions']['#type'] = 'actions';
//    $form['actions']['submit'] = [
//      '#type' => 'submit',
//      '#value' => $this->t('Return order'),
//      '#button_type' => 'primary',
//    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->order;

    if ($form_state->getTriggeringElement()['#id'] == 'edit-submit') {

      // Create new RMA object.
      $name = t('RMAForOrder').$order->label();

      // Create list of new RMA item objects.
      $rma_items = [];
      $order_items = $order->get('order_items')->getValue();

      foreach ($order_items as $order_item_id_mas) {
        $order_item_id = $order_item_id_mas['target_id'];
        /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
        $order_item = OrderItem::load($order_item_id);
        /** @var \Drupal\commerce_rma\Entity\CommerceReturnItemInterface $commerce_return_item */
        $commerce_return_item = CommerceReturnItem::create([
          // TODO Check IT! For test! 'type' must be different!!
          'type' => 'default',
          'name' => $order_item->getTitle(),
          'unit_price' => $order_item->get('unit_price'),
          'quantity' => $order_item->get('quantity'),
          // TODO CHECK THIS field!! Must be normal field!
          'order_item' => $order_item,
        ]);
        $commerce_return_item->save();
        $rma_items[] = $commerce_return_item;
      }

    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $new_rma */
    $new_rma = CommerceReturn::create([
      'name' => $name,
      // TODO CHECK IT For test! - type must be different!!
      'type' => 'default',
      'rma_items' => $rma_items,
      ]);
    $new_rma->save();

    $this->messenger()->addMessage($this->t('Order @label - return requested.', [
      '@label' => $order->label(),
    ]));
    }
  }

}
