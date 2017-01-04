<?php

namespace Drupal\unl_cas\Form\MultiStep;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\unl_cas\Form\UserImportForm;
use Drupal\unl_cas\Helper;
use Drupal\unl_cas\PersonDataQuery;

/**
 * Implements an example form.
 */
class UserImportStepTwoForm extends UserImportForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_cas_user_import_step_two';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //Perform the query
    $search = $this->store->get('unl_import_data');

    $query = new PersonDataQuery();
    $results = $query->search($search);
    
    if (empty($results)) {
      //No results could be found, so restart the process
      drupal_set_message($this->t('No results could be found for: @search', array('@search' => $search)), 'error');
      $form_state->setRedirect('unl_cas.user_import');

      $form['actions']['#type'] = 'actions';
      $form['actions']['start_over'] = array(
        '#title' => $this->t('Start Over'),
        '#type' => 'link',
        '#url' => Url::fromRoute('unl_cas.user_import')
      );
      
      return $form; //exit early
    }
    
    $matches = [];
    foreach ($results as $details) {
      $affiliations = implode(', ', $details['data']['unl']['affiliations']);
      $matches[$details['uid']] = $details['data']['unl']['fullName'] . ' (' . $affiliations . ') (' . $details['uid'] . ')';
    }

    $form['uid'] = array(
      '#type' => 'radios',
      '#title' => sizeof($matches).' Records Found. Select a user to import.',
      '#required' => true,
      '#options' => $matches,
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['start_over'] = array(
      '#title' => $this->t('Start Over'),
      '#type' => 'link',
      '#url' => Url::fromRoute('unl_cas.user_import')
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import Selected User'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //The required field should take care of this
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $helper = new Helper();
    $user = $helper->initializeUser($form_state->getValue('uid'));
    
    drupal_set_message($this->t('imported @uid', array('@uid' => $form_state->getValue('uid'))));
    
    //Redirect to the edit the new user
    $form_state->setRedirect('entity.user.edit_form',array('user' => $user->id()));
  }

}
