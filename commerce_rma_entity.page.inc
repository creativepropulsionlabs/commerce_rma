<?php

/**
 * @file
 * Contains commerce_rma_entity.page.inc.
 *
 * Page callback for RMA entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for RMA templates.
 *
 * Default template: commerce_rma_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_rma_entity(array &$variables) {
  // Fetch RMA Entity Object.
  $commerce_rma_entity = $variables['elements']['#commerce_rma_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
