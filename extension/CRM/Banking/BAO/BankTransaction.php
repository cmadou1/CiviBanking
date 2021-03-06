<?php

/**
 * Class contains functions for CiviBanking bank transactions
 */
class CRM_Banking_BAO_BankTransaction extends CRM_Banking_DAO_BankTransaction {

  /**
   * an array of the structure
   * <probability> => array(<CRM_Banking_Matcher_Suggestion>)
   */
  protected $suggestion_objects = array();

  /**
   * caches a decoded version of the data_parsed field
   */
  protected $_decoded_data_parsed = NULL;

  /**
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return object       CRM_Banking_BAO_BankTransaction object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'BankTransaction', CRM_Utils_Array::value('id', $params), $params);

    // TODO: convert the arrays (suggestions, data_parsed) back into JSON
    $dao = new CRM_Banking_DAO_BankTransaction();
    $dao->copyValues($params);
    $dao->save();

    CRM_Utils_Hook::post($hook, 'BankTransaction', $dao->id, $dao);
    return $dao;
  }

  /**
   * an array of the structure
   * <probability> => array(<CRM_Banking_Matcher_Suggestion>)
   *
   * TODO: after a load/retrieve, need to convert the suggestions/data_parsed from JSON to array
   */
  public function getSuggestions() {
    return $this->suggestion_objects;
  }

  /**
   * will provide a cached version of the decoded data_parsed field
   * if $update=true is given, it will be parsed again
   */
  public function getDataParsed($update=false) {
    if ($this->_decoded_data_parsed==NULL || $update) {
      $this->_decoded_data_parsed = json_decode($this->data_parsed, true);
    }
    return $this->_decoded_data_parsed;
  }

  /**
   * get a suggestion by its hash key
   */
  public function getSuggestionByHash($hash) {
    foreach ($this->suggestion_objects as $probability => $list) {
      foreach ($list as $suggestion) {
        if ($suggestion->getHash() == $hash) {
          return $suggestion;
        }
      }
    }
    return NULL;
  }

  /**
   * get a flat list of CRM_Banking_Matcher_Suggestion
   *
   * @see: getSuggestions()
   */
  public function getSuggestionList() {
    $suggestions = array();
    krsort($this->suggestion_objects);
    foreach ($this->suggestion_objects as $probability => $list) {
      foreach ($list as $item) {
        array_push($suggestions, $item);
      }
    }
    return $suggestions;
  }

  public function resetSuggestions() {
    $this->suggestion_objects = array();
  }

  public function addSuggestion($suggestion) {
    $this->suggestion_objects[floor(100 * $suggestion->getProbability())][] = $suggestion;
  }

  /**
   * Persist suggestiosn for this BTX by converting them into a specific JSON string
   * 
   * TODO: fix problem by which a $bao->save() operation screws up the date values
   */
  public function saveSuggestions() {
    $sugs = array();
    krsort($this->suggestion_objects);
    foreach ($this->suggestion_objects as $probability => $list) {
      foreach ($list as $sug) {
        $sugs[] = $sug->prepForJson();
      }
    }
    $this->suggestions = json_encode($sugs);
    $sql = "
      UPDATE civicrm_bank_tx SET 
      suggestions = '" . mysql_real_escape_string($this->suggestions) . "'
      WHERE id = {$this->id}
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
  }

  /** 
   * Update this BTX's status. Does not use the $bao>save() technique because of the 
   * issue described above.
   * 
   * @param type $status_id
   */
  public function setStatus($status_id) {
    $sql = "
      UPDATE civicrm_bank_tx SET 
      status_id = $status_id
      WHERE id = {$this->id}
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Upon loading a BTX from database, restore suggestions as they were
   * stored in JSON format 
   * 
   * TODO: move the restore to an instance method of Suggestion, thus no longer
   * expising the structure of the Suggestion here
   */
  private function restoreSuggestions() {
    if ($this->suggestion_objects == null && $this->suggestions) {
      $sugs = $this->suggestions;
      if ($sugs != '') {
        $sugs = json_decode($sugs, true);
        foreach ($sugs as $sug) {
          $pi_bao = new CRM_Banking_BAO_PluginInstance();
          $pi_bao->get('id', $sug['plugin_id']);
          $s = new CRM_Banking_Matcher_Suggestion($pi_bao->getInstance(), $this, $sug);
          $this->addSuggestion($s);
        }
      }
    }
  }

  public function get($k, $v) {
    parent::get($k, $v);
    $this->restoreSuggestions();
  }

}

