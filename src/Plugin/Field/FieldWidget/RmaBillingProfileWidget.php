<?php

namespace Drupal\commerce_rma\Plugin\Field\FieldWidget;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_order\Plugin\Field\FieldWidget\BillingProfileWidget;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of 'rma_commerce_billing_profile'.
 *
 * @FieldWidget(
 *   id = "rma_commerce_billing_profile",
 *   label = @Translation("RMA Billing information"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class RmaBillingProfileWidget extends BillingProfileWidget implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_rma\Entity\CommerceReturnInterface $return */
    $return = $items[$delta]->getEntity();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $return->getOrder();
    $store = $order->getStore();
    if (!$items[$delta]->isEmpty() && $items[$delta]->entity) {
      $profile = $items[$delta]->entity;
    }
    else {
      $profile = $this->entityTypeManager->getStorage('profile')->create([
        'type' => 'customer',
        'uid' => $order->getCustomer(),
      ]);
    }
    $inline_form = $this->inlineFormManager->createInstance('customer_profile', [
      'available_countries' => $store->getBillingCountries(),
    ], $profile);

    $element['#type'] = 'fieldset';
    $element['profile'] = [
      '#parents' => array_merge($element['#field_parents'], [$items->getName(), $delta, 'profile']),
      '#inline_form' => $inline_form,
    ];
    $element['profile'] = $inline_form->buildInlineForm($element['profile'], $form_state);
    // Workaround for massageFormValues() not getting $element.
    $element['array_parents'] = [
      '#type' => 'value',
      '#value' => array_merge($element['#field_parents'], [$items->getName(), 'widget', $delta]),
    ];

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_return' && $field_name == 'billing_profile';
  }

}
