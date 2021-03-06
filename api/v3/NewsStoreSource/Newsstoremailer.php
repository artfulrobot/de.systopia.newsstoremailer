<?php

/**
 * NewsStoreSource.NewsstoreMailer API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_news_store_source_NewsstoreMailer_spec(&$spec) {
  // Get a list of Mailing Groups.
  $result = civicrm_api3('Group', 'get', [ 'return' => ["title"], 'group_type' => "Mailing List", 'options' => ['limit' => 0] ]);
  $opts = [];
  foreach ($result['values'] as $id=>$_) {
    $opts[$id] = $_['title'];
  }
  $spec['mailing_group_id'] = [
    'description' => 'ID of Mailing Group to send to',
    'api.required' => 1,
    'options' => $opts,
  ];

  // Get a list of NewsStoreSources.
  $result = civicrm_api3('NewsStoreSource', 'get', ['options' => ['limit' => 0]]);
  $opts = [];
  foreach ($result['values'] as $_) {
    $opts[$_['id']] = $_['name'];
  }
  $spec['news_source_id'] = [
    'description' => 'NewsSourceStore ID',
    'options' => $opts,
    'api.required' => 1,
  ];

  // Get a list of From addresses
  $result = civicrm_api3('OptionValue', 'get', ['option_group_id' => "from_email_address", 'options' => ['limit' => 0]]);
  $opts = [];
  foreach ($result['values'] as $_) {
    $opts[$_['value']] = $_['label'];
  }
  $spec['from_address'] = [
    'description' => 'From Address (must be registered)',
    'options' => $opts,
  ];

  $formatters = ['CRM_NewsstoreMailer' => ts('Default example formatter')];
  CRM_Utils_Hook::singleton()->invoke(1, $formatters,
    $dummy, $dummy, $dummy, $dummy, $dummy,
    'newsstoremailer_formatters');

  $spec['formatter'] = [
    'description' => 'Custom Formatter class (default is CRM_NewsstoreMailer)',
    'options' => $formatters,
  ];

  $spec['test_mode'] = [
    'description' => 'Boolean. If set, mailing will be created but not sent and items will not be marked as consumed.',
  ];

  $spec['created_id'] = [
    'description' => 'Contact ID to record as the creator of this mailing. Must be set for scheduled task calling by cronjob.',
  ];
}

/**
 * NewsStoreSource.NewsstoreMailer API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_NewsstoreMailer($params) {

  // Currently we just use the default templates, but it would be trivial to
  // accept templates here and feed them in, if that functionality is required
  // in future.
  try {
    $formatter = isset($params['formatter']) ? $params['formatter'] : 'CRM_NewsstoreMailer';
    $result = CRM_NewsstoreMailer::factory($formatter, $params)->process();
    return civicrm_api3_create_success(['items_sent' => $result], $params, 'NewsStoreSource', 'NewsstoreMailer');
  }
  catch (\Exception $e) {
    // Rethrow as API exception. (not sure if/why this is important!)
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

