<?php

namespace Drupal\social_post_linkedin\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\social_post_linkedin\LinkedInPostManager;
use Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface;
use Drupal\social_post_linkedin\Post;
use Drupal\social_post\User\UserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Post' action.
 *
 * @RulesAction(
 *   id = "social_post_linkedin",
 *   label = @Translation("LinkedIn Post"),
 *   category = @Translation("Social Post"),
 *   context = {
 *     "status" = @ContextDefinition("string",
 *       label = @Translation("Post content"),
 *       description = @Translation("Specifies the status to post.")
 *     )
 *   }
 * )
 */
class Post extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The social post user manager.
   *
   * @var \Drupal\social_post\User\UserManager
   */
  protected $userManager;

  /**
   * The LinkedIn post manager.
   *
   * @var \Drupal\social_post_linkedin\LinkedInPostManager
   */
  protected $linkedInPostManager;

  /**
   * The Social Post LinkedIn Network plugin.
   *
   * @var \Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface
   */
  protected $linkedInPost;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $linkedin_post = $container->get('plugin.network.manager')->createInstance('social_post_linkedin');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_post.user_manager'),
      $container->get('current_user'),
      $container->get('linkedin_post.manager'),
      $linkedin_post
    );
  }

  /**
   * LinkedIn Post Rules action constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_post\User\UserManager $user_manager
   *   The Social Post user manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\social_post_linkedin\LinkedInPostManager $linkedin_post_manager
   *   The LinkedIn post manager.
   * @param \Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface $linkedin_post
   *   The LinkedIn network plugin.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              UserManager $user_manager,
                              AccountInterface $current_user,
                              LinkedInPostManager $linkedin_post_manager,
                              LinkedInPostInterface $linkedin_post) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userManager = $user_manager;
    $this->currentUser = $current_user;
    $this->linkedInPostManager = $linkedin_post_manager;
    $this->linkedInPost = $linkedin_post;
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $status
   *   The Post text.
   */
  protected function doExecute($status) {
    $client = $this->linkedInPost->getSdk();
    if (!$client) {
      return;
    }

    $this->linkedInPostManager->setClient($client);

    $accounts = $this->userManager->getAccounts('social_post_linkedin', $this->currentUser->id());

    /** @var \Drupal\social_post\Entity\SocialPost $account */
    foreach ($accounts as $account) {
      $post = new Post($status, $account->getProviderUserId());
      $this->linkedInPostManager->doPost($account->getToken(), $post);
    }
  }

}
