<?php

namespace Drupal\commerce_rma\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\commerce_rma\Entity\RMAItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RMAItemController.
 *
 *  Returns responses for RMA item routes.
 */
class RMAItemController extends ControllerBase implements ContainerInjectionInterface {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new RMAItemController.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(DateFormatter $date_formatter, Renderer $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a RMA item revision.
   *
   * @param int $rma_item_revision
   *   The RMA item revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($rma_item_revision) {
    $rma_item = $this->entityTypeManager()->getStorage('rma_item')
      ->loadRevision($rma_item_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('rma_item');

    return $view_builder->view($rma_item);
  }

  /**
   * Page title callback for a RMA item revision.
   *
   * @param int $rma_item_revision
   *   The RMA item revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($rma_item_revision) {
    $rma_item = $this->entityTypeManager()->getStorage('rma_item')
      ->loadRevision($rma_item_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $rma_item->label(),
      '%date' => $this->dateFormatter->format($rma_item->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a RMA item.
   *
   * @param \Drupal\commerce_rma\Entity\RMAItemInterface $rma_item
   *   A RMA item object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(RMAItemInterface $rma_item) {
    $account = $this->currentUser();
    $rma_item_storage = $this->entityTypeManager()->getStorage('rma_item');

    $langcode = $rma_item->language()->getId();
    $langname = $rma_item->language()->getName();
    $languages = $rma_item->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $rma_item->label()]) : $this->t('Revisions for %title', ['%title' => $rma_item->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all rma item revisions") || $account->hasPermission('administer rma item entities')));
    $delete_permission = (($account->hasPermission("delete all rma item revisions") || $account->hasPermission('administer rma item entities')));

    $rows = [];

    $vids = $rma_item_storage->revisionIds($rma_item);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\commerce_rma\RMAItemInterface $revision */
      $revision = $rma_item_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $rma_item->getRevisionId()) {
          $link = $this->l($date, new Url('entity.rma_item.revision', [
            'rma_item' => $rma_item->id(),
            'rma_item_revision' => $vid,
          ]));
        }
        else {
          $link = $rma_item->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.rma_item.translation_revert', [
                'rma_item' => $rma_item->id(),
                'rma_item_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.rma_item.revision_revert', [
                'rma_item' => $rma_item->id(),
                'rma_item_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.rma_item.revision_delete', [
                'rma_item' => $rma_item->id(),
                'rma_item_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['rma_item_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
