<?php

namespace Drupal\Tests\commerce_rma\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

class CommerceRmaBrowserTestBase extends OrderBrowserTestBase {

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_rma\Entity\CommerceReturn
   */
  protected $return;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_rma',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);
    $commerce_return_item = $this->createEntity('commerce_return_item', [
      'type' => 'default',
      'name' => $order_item->getTitle(),
      'unit_price' => $order_item->getUnitPrice(),
      'confirmed_price' => $order_item->getUnitPrice(),
      'quantity' => 1,
      'confirmed_quantity' => 1,
      'order_item' => $order_item->id(),
      'note' => 'test note',
    ]);

    // Create new Return object.
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $commerce_return */
    $commerce_return = $this->createEntity('commerce_return', [
      'name' => 'Return for order 1',
      'type' => 'default',
      'return_items' => [$commerce_return_item],
//      'billing_profile' => $billing_profile,
      'order_id' => $order->id(),
      'user_id' => $order->getCustomerId(),
    ]);

    // Create a product variation.
    $this->return = $commerce_return;
  }

}
