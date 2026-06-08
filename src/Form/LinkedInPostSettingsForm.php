<?php

namespace Drupal\social_post_linkedin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Post LinkedIn.
 */
class LinkedInPostSettingsForm extends ConfigFormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_post_linkedin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_post_linkedin.form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_post_linkedin.settings');

    $form['linkedin_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a LinkedIn App at <a href="@linkedin-dev">@linkedin-dev</a>', ['@linkedin-dev' => 'https://www.linkedin.com/secure/developer']),
    ];

    $form['linkedin_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID of your LinkedIn App here. This value can be found from your App Dashboard.'),
    ];

    $form['linkedin_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret of your LinkedIn App here. This value can be found from your App Dashboard.'),
    ];

    $form['linkedin_settings']['oauth_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your LinkedIn App settings.'),
      '#default_value' => Url::fromRoute('social_post_linkedin.callback', [], ['absolute' => TRUE])->toString(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_post_linkedin.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
