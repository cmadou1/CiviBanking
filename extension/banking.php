<?php

require_once 'banking.civix.php';
require_once 'hooks.php';

/**
 * Implementation of hook_civicrm_config
 */
function banking_civicrm_config(&$config) {
  _banking_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function banking_civicrm_xmlMenu(&$files) {
  _banking_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function banking_civicrm_install() {
  $config = CRM_Core_Config::singleton();
  //create the tables
  $sql = file_get_contents(dirname(__FILE__) . '/sql/banking.sql', true);
  CRM_Utils_File::sourceSQLFile($config->dsn, $sql, NULL, true);

  //add the required option groups
  banking_civicrm_install_options(banking_civicrm_options());

  return _banking_civix_civicrm_install();
}

function banking_civicrm_install_options($data) {
  foreach ($data as $groupName => $group) {
    // check group existence
    $result = civicrm_api('option_group', 'getsingle', array('version' => 3, 'name' => $groupName));
    if (isset($result['is_error']) && $result['is_error']) {
      $params = array(
          'version' => 3,
          'sequential' => 1,
          'name' => $groupName,
          'is_reserved' => 1,
          'is_active' => 1,
          'title' => $group['title'],
          'description' => $group['description'],
      );
      $result = civicrm_api('option_group', 'create', $params);
      $group_id = $result['values'][0]['id'];
    } else
      $group_id = $result['id'];

    if (is_array($group['values'])) {
      $groupValues = $group['values'];
      $weight = 1;
      //print_r(array_keys($groupValues));
      foreach ($groupValues as $valueName => $value) {
        $result = civicrm_api('option_value', 'getsingle', array('version' => 3, 'name' => $valueName));
        if (isset($result['is_error']) && $result['is_error']) {
          $params = array(
              'version' => 3,
              'sequential' => 1,
              'option_group_id' => $group_id,
              'name' => $valueName,
              'label' => $value['label'],
              'value' => $value['value'],
              'weight' => $weight,
              'is_default' => $value['is_default'],
              'is_active' => 1,
          );
          $result = civicrm_api('option_value', 'create', $params);
        } else {
          $weight = $result['weight'] + 1;
        }
      }
    }
  }
}

function banking_civicrm_options() {
  // start with the lowest weight value
  return array(
      'civicrm_banking.plugin_types' => array(
          'title' => 'CiviBanking plugin types',
          'description' => 'The set of possible CiviBanking plugin types',
          'values' => array(
              'dummy' => array(
                  'label' => 'Dummy Data Importer Plugin',
                  'value' => 'CRM_Banking_PluginImpl_Dummy',
                  'is_default' => 0,
              ),
              'generic' => array(
                  'label' => 'Generic Matcher Plugin',
                  'value' => 'CRM_Banking_PluginImpl_Matcher_Generic',
                  'is_default' => 0,
              ),
              'yes' => array(
                  'label' => 'Dummy Matcher Test Plugin',
                  'value' => 'CRM_Banking_PluginImpl_Matcher_Yes',
                  'is_default' => 0,
              ),
              'csv_import' => array(
                  'label' => 'Configurable CSV Importer',
                  'value' => 'CRM_Banking_PluginImpl_CSVImporter',
                  'is_default' => 0,
              ),
          ),
      ),
      'civicrm_banking.reference_types' => array(
          'title' => 'CiviBanking bank account reference types',
          'description' => 'The set of possible CiviBanking bank account reference types',
          'values' => array(
              'IBAN' => array(
                  'label' => 'International Bank Account Number',
                  'value' => 'IBAN',
                  'is_default' => 1,
              ),
              'NBAN_DE' => array(
                  'label' => 'German (national) Bank Account Number',
                  'value' => 'NBAN_DE',
                  'is_default' => 0,
              ),
              'NBAN_BE' => array(
                  'label' => 'Belgian (national) Bank Account Number',
                  'value' => 'NBAN_BE',
                  'is_default' => 0,
              ),
          ),
      ),
      'civicrm_banking.plugin_classes' => array(
          'title' => 'CiviBanking plugin classes',
          'description' => 'The set of existing CiviBanking plugin types',
          'values' => array(
              'import' => array(
                  'label' => 'Import plugin',
                  'value' => 1,
                  'is_default' => 0,
              ),
              'match' => array(
                  'label' => 'Match plugin',
                  'value' => 2,
                  'is_default' => 0,
              ),
              'export' => array(
                  'label' => 'Export plugin',
                  'value' => 3,
                  'is_default' => 0,
              ),
          ),
      ),
      'civicrm_banking.bank_tx_status' => array(
          'title' => 'CiviBanking bank transaction processing status',
          'description' => 'The set of possible processing statuses for a CiviBanking bank transaction',
          'values' => array(
              'new' => array(
                  'label' => 'New',
                  'value' => 0,
                  'is_default' => 1,
              ),
              'ignored' => array(
                  'label' => 'Ignored',
                  'value' => 1,
                  'is_default' => 0,
              ),
              'suggestions' => array(
                  'label' => 'Suggestions',
                  'value' => 2,
                  'is_default' => 0,
              ),
              'processed' => array(
                  'label' => 'Processed',
                  'value' => 3,
                  'is_default' => 0,
              ),
          ),
      ),
  );
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function banking_civicrm_uninstall() {
  return _banking_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function banking_civicrm_enable() {
  //add the required option groups
  banking_civicrm_install_options(banking_civicrm_options());

  return _banking_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function banking_civicrm_disable() {
  return _banking_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function banking_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _banking_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function banking_civicrm_managed(&$entities) {
  return _banking_civix_civicrm_managed($entities);
}

