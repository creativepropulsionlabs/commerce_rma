<?php

namespace Drupal\commerce_rma\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\commerce_rma\Entity\CommerceReturnInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommerceReturnController.
 *
 *  Returns responses for RMA routes.
 */
class CommerceReturnController extends ControllerBase implements ContainerInjectionInterface {


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
   * Constructs a new CommerceReturnController.
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
   * Displays a RMA revision.
   *
   * @param int $rma_revision
   *   The RMA revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($rma_revision) {
    $rma = $this->entityTypeManager()->getStorage('rma')
      ->loadRevision($rma_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('rma');

    return $view_builder->view($rma);
  }

  /**
   * Page title callback for a RMA revision.
   *
   * @param int $rma_revision
   *   The RMA revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($rma_revision) {
    $rma = $this->entityTypeManager()->getStorage('rma')
      ->loadRevision($rma_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $rma->label(),
      '%date' => $this->dateFormatter->format($rma->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a RMA.
   *
   * @param \Drupal\commerce_rma\Entity\CommerceReturnInterface $rma
   *   A RMA object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CommerceReturnInterface $rma) {
    $account = $this->currentUser();
    $rma_storage = $this->entityTypeManager()->getStorage('rma');

    $langcode = $rma->language()->getId();
    $langname = $rma->language()->getName();
    $languages = $rma->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $rma->label()]) : $this->t('Revisions for %title', ['%title' => $rma->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all rma revisions") || $account->hasPermission('administer rma entities')));
    $delete_permission = (($account->hasPermission("delete all rma revisions") || $account->hasPermission('administer rma entities')));

    $rows = [];

    $vids = $rma_storage->revisionIds($rma);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\commerce_rma\RMAInterface $revision */
      $revision = $rma_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $rma->getRevisionId()) {
          $link = $this->l($date, new Url('entity.rma.revision', [
            'rma' => $rma->id(),
            'rma_revision' => $vid,
          ]));
        }
        else {
          $link = $rma->link($date);
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
              Url::fromRoute('entity.rma.translation_revert', [
                'rma' => $rma->id(),
                'rma_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.rma.revision_revert', [
                'rma' => $rma->id(),
                'rma_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.rma.revision_delete', [
                'rma' => $rma->id(),
                'rma_revision' => $vid,
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

    $build['rma_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
