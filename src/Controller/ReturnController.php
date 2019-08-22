<?php

namespace Drupal\commerce_rma\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the add-page and title callbacks for return.
 */
class ReturnController extends EntityController {

  /**
   * Redirects to the return add form.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order to add a return to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the return add page.
   */
  public function addReturnPage(OrderInterface $commerce_order) {
    $order_type = $this->entityTypeManager->getStorage('commerce_order_type')->load($commerce_order->bundle());
    // Find the return type associated to this order type.
    $return_type = $order_type->getThirdPartySetting('commerce_rma', 'return_type', 'default');
    $collection_url = Url::fromRoute('entity.commerce_return.collection',[
      'commerce_order' => $commerce_order->id(),
    ])->toString();
    $destination = parse_url(\Drupal::request()->server->get('HTTP_REFERER'));

    return $this->redirect('entity.commerce_return.add_form', [
      'commerce_order' => $commerce_order->id(),
      'commerce_return_type' => $return_type,
    ],[
      'query' => ['destination' => $destination["path"]],
//      'absolute' => TRUE,
    ]);
  }

}
