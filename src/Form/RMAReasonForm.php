<?php

namespace Drupal\commerce_rma\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RMAReasonForm.
 */
class RMAReasonForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $rma_reason = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rma_reason->label(),
      '#description' => $this->t("Label for the RMAReason."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rma_reason->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rma\Entity\RMAReason::load',
      ],
      '#disabled' => !$rma_reason->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rma_reason = $this->entity;
    $status = $rma_reason->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label RMAReason.', [
          '%label' => $rma_reason->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label RMAReason.', [
          '%label' => $rma_reason->label(),
        ]));
    }
    $form_state->setRedirectUrl($rma_reason->toUrl('collection'));
  }

}
