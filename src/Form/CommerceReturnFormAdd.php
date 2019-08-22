<?php

namespace Drupal\commerce_rma\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller for RMA edit forms.
 *
 * @ingroup commerce_rma
 */
class CommerceReturnFormAdd extends ContentEntityForm {

  /**
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CommerceReturnForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account, RequestStack $request_stack, RouteMatchInterface $route_match) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->account = $account;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_rma\Entity\CommerceReturn $entity */
    $commerce_order = \Drupal::routeMatch()->getParameter('commerce_order');
//    $target_id = $this->requestStack->getCurrentRequest()->query->get('target_id');
    $this->entity->set('order_id', $commerce_order->id());
    $this->entity->set('name', 'Return of order ' .$commerce_order->id());
    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $commerce_order->getBillingProfile();
    if (!$billing_profile) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $billing_profile = $profile_storage->create([
        'type' => 'customer',
        'uid' => $commerce_order->getCustomerId(),
      ]);
    }
    $this->entity->set('billing_profile', $billing_profile->id());
    $order_item_ids = [];
    foreach ($commerce_order->getItems() as $order_item) {
      $order_item_ids[] = $order_item->id();
    }
    $this->entity->set('return_items', $order_item_ids);

    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

//    $values = $form_state->getValues();
//    $state = $values['state'];
//    $entity->set('state', $state);
//    $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label RMA.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label RMA.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.commerce_return.collection', ['commerce_return' => $entity->id()]);
  }

}
