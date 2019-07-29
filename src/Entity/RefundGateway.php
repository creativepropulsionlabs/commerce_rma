<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\commerce\CommerceSinglePluginCollection;
use Drupal\commerce\ConditionGroup;
use Drupal\commerce\Plugin\Commerce\Condition\ParentEntityAwareInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the refund gateway entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_refund_gateway",
 *   label = @Translation("Refund gateway"),
 *   label_collection = @Translation("Refund gateways"),
 *   label_singular = @Translation("refund gateway"),
 *   label_plural = @Translation("refund gateways"),
 *   label_count = @PluralTranslation(
 *     singular = "@count refund gateway",
 *     plural = "@count refund gateways",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_rma\RefundGatewayListBuilder",
 *     "storage" = "Drupal\commerce_rma\RefundGatewayStorage",
 *     "form" = {
 *       "add" = "Drupal\commerce_rma\Form\RefundGatewayForm",
 *       "edit" = "Drupal\commerce_rma\Form\RefundGatewayForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_refund_gateway",
 *   config_prefix = "commerce_refund_gateway",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "status",
 *     "plugin",
 *     "configuration",
 *     "conditions",
 *     "conditionOperator",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/refund-gateways/add",
 *     "edit-form" = "/admin/commerce/config/refund-gateways/manage/{commerce_refund_gateway}",
 *     "delete-form" = "/admin/commerce/config/refund-gateways/manage/{commerce_refund_gateway}/delete",
 *     "collection" =  "/admin/commerce/config/refund-gateways"
 *   }
 * )
 */
class RefundGateway extends ConfigEntityBase implements RefundGatewayInterface {

  /**
   * The refund gateway ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The refund gateway label.
   *
   * @var string
   */
  protected $label;

  /**
   * The refund gateway weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The condition operator.
   *
   * @var string
   */
  protected $conditionOperator = 'AND';

  /**
   * The plugin collection that holds the refund gateway plugin.
   *
   * @var \Drupal\commerce\CommerceSinglePluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->configuration = [];
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $plugin_manager = \Drupal::service('plugin.manager.commerce_condition');
    $conditions = [];
    foreach ($this->conditions as $condition) {
      $condition = $plugin_manager->createInstance($condition['plugin'], $condition['configuration']);
      if ($condition instanceof ParentEntityAwareInterface) {
        $condition->setParentEntity($this);
      }
      $conditions[] = $condition;
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionOperator() {
    return $this->conditionOperator;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionOperator($condition_operator) {
    $this->conditionOperator = $condition_operator;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    $conditions = $this->getConditions();
    if (!$conditions) {
      // Refund gateways without conditions always apply.
      return TRUE;
    }
    $order_conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'commerce_order';
    });
    $order_conditions = new ConditionGroup($order_conditions, $this->getConditionOperator());

    return $order_conditions->evaluate($order);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Invoke the setters to clear related properties.
    if ($property_name == 'plugin') {
      $this->setPluginId($value);
    }
    elseif ($property_name == 'configuration') {
      $this->setPluginConfiguration($value);
    }
    else {
      return parent::set($property_name, $value);
    }
  }

  /**
   * Gets the plugin collection that holds the refund gateway plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce\CommerceSinglePluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_refund_gateway');
      $this->pluginCollection = new CommerceSinglePluginCollection($plugin_manager, $this->plugin, $this->configuration, $this->id);
    }
    return $this->pluginCollection;
  }

}
