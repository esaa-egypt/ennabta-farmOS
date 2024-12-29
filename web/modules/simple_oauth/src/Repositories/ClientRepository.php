<?php

namespace Drupal\simple_oauth\Repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\simple_oauth\Entities\ClientEntity;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * The client repository.
 */
class ClientRepository implements ClientRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $passwordChecker;

  /**
   * Constructs a ClientRepository object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PasswordInterface $password_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->passwordChecker = $password_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientEntity($clientIdentifier) {
    $client_drupal_entities = $this->entityTypeManager
      ->getStorage('consumer')
      ->loadByProperties(['client_id' => $clientIdentifier]);

    // Check if the client is registered.
    if (empty($client_drupal_entities)) {
      return NULL;
    }
    $client_drupal_entity = reset($client_drupal_entities);

    return new ClientEntity($client_drupal_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType) {
    $client_entity = $this->getClientEntity($clientIdentifier);
    if (!$client_entity) {
      return FALSE;
    }
    $client_drupal_entity = $client_entity->getDrupalEntity();

    // For the client credentials grant type a default user is required.
    if ($grantType === 'client_credentials' && !$client_drupal_entity->get('user_id')->entity) {
      throw OAuthServerException::serverError('Invalid default user for client.');
    }

    // Determine if a client secret is configured. The client may omit the
    // parameter if the configured secret is NULL or if the value of the
    // secret is the hash of an empty string.
    // @see https://datatracker.ietf.org/doc/html/rfc6749#section-2.3.1
    $secret_field = $client_drupal_entity->get('secret');
    $secret_field_is_empty = $secret_field->isEmpty() || $this->passwordChecker->check('', $secret_field->value);

    // The client_credentials grant is specifically special-cased, the
    // client credentials grant type MUST only be used by confidential clients.
    // @see https://datatracker.ietf.org/doc/html/rfc6749#section-4.4
    if ($grantType === 'client_credentials' && $secret_field_is_empty) {
      return FALSE;
    }

    // Validate a client without a client secret if the client is explicitly
    // configured to be non-confidential. Note that if a client secret is
    // provided it should be validated, even if the client is non-confidential.
    if (!$client_drupal_entity->get('confidential')->value &&
      $secret_field_is_empty &&
      empty($clientSecret)) {
      return TRUE;
    }

    // Check if a secret has been provided for this client and validate it.
    // @see https://datatracker.ietf.org/doc/html/rfc6749#section-3.2.1
    return $clientSecret && $this->passwordChecker->check($clientSecret, $secret_field->value);
  }

}
