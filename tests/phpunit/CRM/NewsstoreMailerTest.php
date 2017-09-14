<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests API for NewsstoreMailer class.
 *
 * @group headless
 */
class NewsstoreMailerTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * List of stuff to delete after a test.
   */
  public $cleanup_api_calls = [];
  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown() {
    // Delete stuff we made.
    while ($_ = array_pop($this->cleanup_api_calls)) {
      civicrm_api3($_[0], $_[1], $_[2]);
    }
    parent::tearDown();
  }

  /**
   * Test no mailing is created if no items to send.
   */
  public function testNothingSentIfNoItems() {
    $a = new CRM_NewsstoreMailer([
      'body_tpl' => '',
      'item_tpl' => '',
    ]);
    $result = $a->createMailing([], 1);
    $this->assertEquals(null, $result);
  }
  public function testTemplateWorks() {
    $a = new CRM_NewsstoreMailer([
      'body_tpl' => "<p>header</p>%ITEMS%<p>footer</p>",
      'item_tpl' => "<p class='item' >item: %ITEM_TITLE%\ndetail: %ITEM_TEASER%\n<a href='%ITEM_LINK%' >Read More</a></p>",
    ]);
    $result = $a->getMailingHtml(
      [
        [
          'id'          => 1,
          'uri'         => 'https://example.com/1',
          'title'       => 'Test item 1',
          'description' => '<p>This is the <strong>HTML</strong> 1.</p>',
          'teaser'      => 'Teaser text 1',
          'object'      => '',
        ],
      ]);
    $this->assertEquals("<p>header</p><p class='item' >item: Test item 1\ndetail: Teaser text 1\n<a href='https://example.com/1' >Read More</a></p><p>footer</p>", $result);
  }
  /**
   * Test mailing is created.
   */
  public function testMailingCreated() {

    // Need a mailing group.
    $group_id = $this->createTestMailingGroup();

    $a = new CRM_NewsstoreMailer([
      'body_tpl' => '',
      'item_tpl' => '',
    ]);
    $mailing_id = $a->createMailing([
      [
        'id'          => 1,
        'uri'         => 'https://example.com/1',
        'title'       => 'Test item 1',
        'description' => '<p>This is the <strong>HTML</strong> 1.</p>',
        'teaser'      => 'Teaser text 1',
        'object'      => '',
      ],
    ], $group_id);
    $this->assertGreaterThan(0, $mailing_id);
  }
  /**
   * Helper function.
   *
   * Creates a contact, an email and a mailing group.
   *
   * @return int ID of group created.
   */
  public function createTestMailingGroup() {
    // Create contact.
    $contact = civicrm_api3('contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Wilma',
      'last_name' => 'Flintstone',
    ]);
    $this->cleanup_api_calls[] = ['Contact', 'delete', ['id' => $contact['id'], 'skip_undelete' => TRUE]];
    $email = civicrm_api3('email', 'create', [
      'contact_id' => $contact['id'],
      'email' => 'wilma@example.com',
    ]);
    $this->cleanup_api_calls[] = ['Email', 'delete', ['id' => $email['id']]];

    // Create group.
    $group = civicrm_api3('group', 'create', [
      'title' => "Test group 1",
      'name' => "test_group_1",
      'group_type' => "Mailing List",
    ]);
    $this->cleanup_api_calls[] = ['Group', 'delete', ['id' => $group['id']]];
    return $group['id'];
  }
}

