<?php

namespace Drupal\Tests\commerce_rma\Functional;

class TotalsAdminTest extends CommerceRmaBrowserTestBase{

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the count text (display, plurality), and the cart dropdown.
   */
  public function testRMAStatus() {
    $this->drupalGet('admin/commerce/returns');
    $this->assertSession()->pageTextContains('Draft');

//    $this->cartManager->addEntity($this->cart, $this->variation);
//    $this->drupalGet('<front>');
//    $this->assertSession()->pageTextContains('1 item');
//    $this->assertSession()->pageTextContains($this->variation->getOrderItemTitle());
//    $this->assertSession()->pageTextContains('1 x');
//
//    $this->cartManager->addEntity($this->cart, $this->variation, 2);
//    $this->drupalGet('<front>');
//    $this->assertSession()->pageTextContains('3 items');
//    $this->assertSession()->pageTextContains('3 x');
//
//     If the order is no longer a draft, the block should not render.
//    $workflow = $this->cart->getState()->getWorkflow();
//    $this->cart->getState()->applyTransition($workflow->getTransition('place'));
//    $this->cart->save();

//    $this->drupalGet('<front>');
//    $this->assertSession()->pageTextNotContains('3 items');
  }

}
