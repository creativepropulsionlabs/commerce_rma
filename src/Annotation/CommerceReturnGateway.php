<?php

namespace Drupal\commerce_rma\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the payment gateway plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\PaymentGateway.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceReturnGateway extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The payment gateway label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The payment gateway display label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $display_label;

  /**
   * The supported modes.
   *
   * An array of mode labels keyed by machine name.
   *
   * @var string[]
   */
  public $modes;

  /**
   * The payment gateway forms.
   *
   * An array of form classes keyed by operation.
   * For example:
   * <code>
   *   'add-payment-method' =>
   * "Drupal\commerce_payment\PluginForm\PaymentMethodAddForm",
   *   'capture-payment' =>
   * "Drupal\commerce_payment\PluginForm\PaymentCaptureForm",
   * </code>
   *
   * @var array
   */
  public $forms = [];

  /**
   * The JS library ID.
   *
   * @var string
   */
  public $js_library;

  /**
   * The payment type used by the payment gateway.
   *
   * @var string
   */
  public $return_type = 'default';

  /**
   * Constructs a new CommerceReturnGateway object.
   *
   * @param array $values
   *   The annotation values.
   */
  public function __construct(array $values) {
    if (empty($values['modes'])) {
      $values['modes'] = [
        'test' => new TranslatableMarkup('Test'),
        'live' => new TranslatableMarkup('Live'),
      ];
    }
    parent::__construct($values);
  }

}
