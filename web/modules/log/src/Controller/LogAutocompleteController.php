<?php

namespace Drupal\log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for log names.
 */
class LogAutocompleteController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a LogAutocompleteController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Retrieves suggestions for log name autocompletion.
   *
   * @param string $log_bundle
   *   The log bundle name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function autocomplete(string $log_bundle, Request $request) {
    $matches = [];

    if ($input = $request->query->get('q')) {
      // A regular database query is used so the results returned can be sorted
      // by usage.
      $table_mapping = $this->entityTypeManager()->getStorage('log')->getTableMapping();
      $query = $this->database->select($table_mapping->getDataTable(), 'log_field_data');
      $query->fields('log_field_data', ['name']);
      $query->addExpression('COUNT(name)', 'count');
      $query->condition('type', $log_bundle);
      $query->condition('name', '%' . $this->database->escapeLike($input) . '%', 'LIKE');

      // Because a regular database query is used to sort by the usage of the
      // log names, a minimal access control is done here.
      // If the user has administer log or can view any log entity from any
      // bundle, no further condition is added, if the user can see their own
      // entities, the query is restricted by user, otherwise an empty set is
      // returned.
      switch ($this->typeOfAccess($log_bundle)) {
        case 'none':
          return new JsonResponse([]);

        case 'own':
          $query->condition('uid', $this->currentUser()->id());
          break;

        case 'any':
        default:
          // Nothing to do, full access.
      }

      $query->groupBy('name');
      $query->orderBy('count', 'DESC');
      $query->orderBy('name', 'ASC');

      $matches = $query->execute()->fetchCol();
    }

    return new JsonResponse($matches);
  }

  /**
   * Helper function that returns what filter must be applied to the user query.
   *
   * @param string $log_bundle
   *   The log bundle.
   *
   * @return string
   *   'any' => Full access.
   *   'own' => Access to own logs.
   *   'none' => No access to logs.
   */
  protected function typeOfAccess(string $log_bundle) {
    $account = $this->currentUser();

    if ($account->hasPermission('administer log') || $account->hasPermission('view any ' . $log_bundle . ' log')) {
      return 'any';
    }
    if ($account->hasPermission('view own ' . $log_bundle . ' log')) {
      return 'own';
    }

    return 'none';
  }

}
