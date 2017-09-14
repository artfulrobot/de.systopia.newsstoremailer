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
  $spec['mailing_group_id'] = [
    'description' => 'ID of Mailing Group to send to',
    'api.required' => 1,
  ];
  $spec['news_source_id'] = [
    'description' => 'NewsSourceStore ID',
    'api.required' => 1,
  ];
  $spec['test_mode'] = [
    'description' => 'Boolean. If set, mailing will be created but not sent and items will not be marked as consumed.',
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

