<?php

namespace Drupal\commerce_rma\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the RMA entity.
 *
 * @ingroup commerce_rma
 *
 * @ContentEntityType(
 *   id = "rma",
 *   label = @Translation("RMA"),
 *   bundle_label = @Translation("RMA type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_rma\RMAStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_rma\RMAListBuilder",
 *     "views_data" = "Drupal\commerce_rma\Entity\RMAViewsData",
 *     "translation" = "Drupal\commerce_rma\RMATranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_rma\Form\RMAForm",
 *       "add" = "Drupal\commerce_rma\Form\RMAForm",
 *       "edit" = "Drupal\commerce_rma\Form\RMAForm",
 *       "delete" = "Drupal\commerce_rma\Form\RMADeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_rma\RMAHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\commerce_rma\RMAAccessControlHandler",
 *   },
 *   base_table = "rma",
 *   data_table = "rma_field_data",
 *   revision_table = "rma_revision",
 *   revision_data_table = "rma_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer rma entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rma/{rma}",
 *     "add-page" = "/admin/structure/rma/add",
 *     "add-form" = "/admin/structure/rma/add/{rma_type}",
 *     "edit-form" = "/admin/structure/rma/{rma}/edit",
 *     "delete-form" = "/admin/structure/rma/{rma}/delete",
 *     "version-history" = "/admin/structure/rma/{rma}/revisions",
 *     "revision" = "/admin/structure/rma/{rma}/revisions/{rma_revision}/view",
 *     "revision_revert" = "/admin/structure/rma/{rma}/revisions/{rma_revision}/revert",
 *     "revision_delete" = "/admin/structure/rma/{rma}/revisions/{rma_revision}/delete",
 *     "translation_revert" = "/admin/structure/rma/{rma}/revisions/{rma_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/rma",
 *   },
 *   bundle_entity_type = "rma_type",
 *   field_ui_base_route = "entity.rma_type.edit_form"
 * )
 */
class RMA extends RevisionableContentEntityBase implements RMAInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;


  /**
   * The initialized states.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowState[]
   */
  protected $states = [];

  /**
   * The RMA items.
   *
   * @var \Drupal\commerce_rma\Entity\RMAItem[]
   */
  protected $items = [];

  /**
   * {@inheritdoc}
   */
  public function getStates() {
    return $this->states;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $items) {
    $this->$items = $items;
  }



  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the rma owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the RMA entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the RMA entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the RMA is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
