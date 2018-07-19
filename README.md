# NewsStore Mailer

This extension provides an API method which takes 'unconsumed' items from a
given NewsStoreSource, formats them into an email, and sends that email to a
given mailing list.

If there are no unconsumed items then no mailing is sent.

## API Action: `NewsStoreSource.newsStoreMailer`

This action takes:
- `news_source_id`   The NewsStoreSource ID.
- `mailing_group_id`
- `formatter`        Class name to format the items into mailing content.
- `test_mode`        (optional)

The process is:

1. Check for unconsumed items in the NewsStoreSource. Exits here if there aren't
   any.

2. Creates a mailing to the given group using the formatter class (see below
   (see below).

3. If not in test mode: submit the mailing for sending.

4. If not in test mode: mark all the items it used as consumed.

This could then be set up as a scheduled job.

## Formatter classes

Formatters must extend `CRM_NewsstoreMailer`. There are two key methods to
implement:

- `getMailingHtml`
- `getMailingSubject`
- `alterCreateMailingParams`

Both the `getMailing*` methods are called with an array of items from the
NewsStoreSource for formatting.

After calling these methods to generate the content of a mailing, an API call is
prepared on the API action `Mailing.create`. The parameters for this call are
first filtered by `alterCreateMailingParams`. This can be useful to tweak any
final settings that your formatter may require (e.g. to apply filters for
Mosaico).
