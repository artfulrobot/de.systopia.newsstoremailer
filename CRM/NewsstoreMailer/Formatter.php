<?php

/**
 * This is the minimal interface required for the API call NewsStoreSource.NewsstoreMailer
 *
 * However you will most likely want to extend CRM_NewsstoreMailer (which
 * implements this interface), usually just to override its getMailingHtml(),
 * getMailingSubject() and alterCreateMailingParams() methods.
 *
 */
interface CRM_NewsstoreMailer_Formatter
{
  /**
   * Factory method.
   *
   * @param string $mailer_class should inherit from class (CRM_NewsstoreMailer)
   * @param array $params from the API call. Should include `mailing_group_id`
   *                      and `news_source_id`.
   * @return CRM_NewsstoreMailer
   */
  public static function factory($mailer_class='CRM_NewsstoreMailer', $params=[]);

  /**
   * Main calling method.
   *
   * This is responsible for fetching the appropriate
   */
  public function process();
}
