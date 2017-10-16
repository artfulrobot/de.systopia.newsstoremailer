<?php
/**
 * This is the parent class of all mailing formatters.
 *
 * Formatters should implement getMailingHtml() and getMailingSubject() and
 * configure() if they need to take input params.
 */
class CRM_NewsstoreMailer
{
  /** array output of a NewsStoreSource getsingle call. */
  public $source;
  /** array output of a Group getsingle call. */
  public $mailing_group;
  /** bool 'test_mode' If TRUE, do not send the mailing and do not mark items consumed. */
  public $test_mode = FALSE;
  /** string Friendly from name. */
  public $from_name;
  /** string From email. */
  public $from_email;

  /**
   * Factory method.
   *
   * @param string $mailer_class must inherit from this class (CRM_NewsstoreMailer)
   * @return CRM_NewsstoreMailer
   */
  public static function factory($mailer_class='CRM_NewsstoreMailer', $params=[]) {

    if ($mailer_class != __CLASS__) {
      if (!class_exists($mailer_class, $autoload=TRUE)) {
        throw new \Exception("'$mailer_class' is not defined.");
      }
      $reflection = new \ReflectionClass($mailer_class);
      if (! $reflection->isSubclassOf('CRM_NewsstoreMailer')) {
        throw new \Exception("'$mailer_class' is not valid.");
      }
    }
    $obj = new $mailer_class($params);
    return $obj;
  }
  /**
   * Constructor.
   *
   * @param array $params
   * In the constructor we care about the following keys, but implementing
   * classes should override configure() for anything they need to do extra.
   *
   * - int 'news_source_id'
   * - int 'mailing_group_id'
   * - bool 'test_mode' If TRUE, do not send the mailing and do not mark items consumed.
   *
   */
  public function __construct($params = []) {

    if (isset($params['news_source_id'])) {
      $this->source = civicrm_api3('NewsStoreSource', 'getsingle', ['id' => $params['news_source_id']]);
    }

    if (isset($params['mailing_group_id'])) {
      $this->mailing_group = civicrm_api3('Group', 'getsingle', [
        'id'         => $params['mailing_group_id'],
        'group_type' => "Mailing List",
        'is_active'  => 1,
        ]);
    }

    $this->test_mode = (!empty($params['test_mode']));

    // Attempt to set From to the site's default.
    $from = civicrm_api3('OptionValue', 'get', [
      'sequential'      => 1,
      'return'          => ["label", 'is_default'],
      'is_default'      => 1,
      'option_group_id' => "from_email_address",
    ]);
    if ($from['count']
      && preg_match('/^"([^"]+)"\s+<([^>]+)>$/', $from['values'][0]['label'], $from_email)) {

      $this->from_name  = $from_email[1];
      $this->from_email = $from_email[2];
    }

    $this->configure($params);
  }
  /**
   * Configure from input params according to this formatter's requirements.
   *
   * @param array $params
   * @return CRM_NewsstoreMailer $this
   */
  public function configure($params=[]) {
  }

  /**
   * Send items out.
   *
   * @return int number of items included in the mailing sent.
   */
  public function process() {

    // Fetch data.
    $items = civicrm_api3('NewsStoreItem', 'getwithusage', array(
      'source'      => $this->source['id'],
      'is_consumed' => 0, // Only unconsumed items.
    ));

    // Create mailing.
    $mailing_id = $this->createMailing($items['values']);
    if ($mailing_id) {
      if (!$this->test_mode) {
        // NOT test mode.
        $this->sendMailing($mailing_id);

        if ($items['values']) {
          // Mark each of these items consumed.
          foreach ($items['values'] as $item) {
            $result = civicrm_api3('NewsStoreConsumed', 'create', [
              'id'          => $item['newsstoreconsumed_id'],
              'is_consumed' => 1,
            ]);
          }
        }
      }
    }

    return (int) $items['count'];
  }
  /**
   * Create a CiviMail mailing.
   *
   * @return null|int ID of mailing created, if one was.
   */
  public function createMailing($items) {
    if (empty($items)) {
      // Nothing to do, don't do anything!
      return;
    }

    if (empty($this->from_email)) {
      throw new \Exception("Missing FROM email address.");
    }

    // Got items. Create a mailing.
    $params = [
      'sequential' => 1,
      'name'       => ts(count($items)>1 ? count($items) . " items: " : '1 item: ') . $this->mailing_group['title'] . ' ' . date('j M Y'),
      'from_name'  => $this->from_name,
      'from_email' => $this->from_email,
      'subject'    => $this->getMailingSubject($items),
      'body_html'  => $this->getMailingHtml($items),
      'groups'     => ['include' => [$this->mailing_group['id']]],
      'header_id'  => '',
      'footer_id'  => '',
    ];
    // file_put_contents("/tmp/automail.html", $params['body_html']);
    $mailing_result = civicrm_api3('Mailing', 'create', $params);

    return $mailing_result['id'];
  }
  /**
   * Schedule the mailing to be sent immediately.
   *
   * @todo schedule 30 mins in future?
   */
  public function sendMailing($mailing_id) {
    // Send it.
    $submit_result = civicrm_api3('Mailing', 'submit', [
      'id' => $mailing_id,
      'scheduled_date' => date('Y-m-d H:i:s'),
      'approval_date' => date('Y-m-d H:i:s'),
      ]);
    return $submit_result;
  }

  /**
   * Template the email.
   *
   * This is a minimal sample implementation.
   */
  public function getMailingHtml($items) {

    $html = "<p>Dear {contact.first_name},</p><p>Here's " . count($items) . " articles:</p>";
    foreach ($items as $item) {
      $html .= "<article><h2>" . htmlspecialchars(strip_tags($item['title'])) . "</h2>"
        . htmlspecialchars($item['teaser'])
        . "<a href=" . $item['uri'] . ">Read Full Story</a>"
        . "</article>";
    }
    $html .= "<p>You can <a href='{action.unsubscribeUrl}'>unsubscribe</a>.</p><p>{domain.address}</p>";

    return $html;
  }
  /**
   * Template the email.
   *
   * This is a minimal sample implementation.
   */
  public function getMailingSubject($items) {
    return count($items) . " articles from " . $this->source['name'];
  }
}
