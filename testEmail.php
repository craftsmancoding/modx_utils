<?php
/**
 * testEmail
 *
 * A simple Snippet for testing email functionality in MODX Revolution.
 *
 * Put this Snippet on a page in your MODX installation:
 *
 *  [[!testEmail? &to=`your@email.com`]]
 *
 * And then verify that you got a message in your inbox.
 *
 * Parameters:
 *  &to (string) required.  Your email address: check here to see if it worked.
 *  &from (string) optional. The from email address. Default: emailsender System Setting.
 *  &fromname (string) optional. The name of the sender. Default: site_name System Setting.
 *  &replyto (string) optional. The reply-to email address. Default: emailsender System Setting.
 *  &subject (string) optional. A short subject for the email.
 *  &service (string) optional. A valid MODX email class. Default mail.modPHPMailer
 *  &html (boolean) optional. 1 for HTML emails, 0 for text.
 *  &msg (string) optional. The text of the message.
 *  
 *
 * http://craftsmancoding.com/
 */
$p = array(); // properties
$p['to'] = $modx->getOption('to',$scriptProperties);
$p['from'] = $modx->getOption('from',$scriptProperties, $modx->getOption('emailsender'));
$p['fromname'] = $modx->getOption('fromname',$scriptProperties, $modx->getOption('site_name'));
$p['replyto'] = $modx->getOption('replyto',$scriptProperties, $modx->getOption('emailsender'));
$p['subject'] = $modx->getOption('subject',$scriptProperties, 'Test Email Functionality');
$p['service'] = $modx->getOption('service',$scriptProperties, 'mail.modPHPMailer');
$p['html'] = $modx->getOption('html',$scriptProperties, true);
$msg = $modx->getOption('msg',$scriptProperties, 'This is a test email from the testEmail Snippet on the [[++site_name]] website. It was sent from [[+from]] to [[+to]].');


// Formatting Strings with inline styling
$errorTpl = '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
    <span style="color:red; font-weight:bold;">Error</span><br />
    <p>%s</p></div>';
$successTpl = '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>%s</p></div>';

if (empty($p['to'])) {
    return sprintf($errorTpl,'[testEmail]: The &to parameter is required.');
}

// Our informational Chunk
$settings = array('emailsender','allow_multiple_emails','mail_smtp_auth','mail_smtp_helo','mail_smtp_hosts','mail_smtp_keepalive'
,'mail_smtp_pass','mail_smtp_port','mail_smtp_prefix','mail_smtp_single_to','mail_smtp_timeout','mail_smtp_user','mail_use_smtp');

$rows = '';
foreach ($settings as $s) {
    $rows .= sprintf('<tr><td><strong>%s</strong></td><td>[[++%s]]</td><td><em>[[!%s? &topic=`setting` &namespace=`core` &language=`en`]]</em></td></tr>',$s,$s,'%setting_'.$s.'_desc');
}

$infoTpl = '
<div style="margin:10px; padding:20px; border:1px solid gray; border-radius: 5px; width:500px;">
<h3>Script Properties</h3>
<pre>'.
print_r($p,true)
.'</pre>
<h3>Email Related System Settings</h3>
<table>
    <thead>
        <tr>
            <th>System Setting</th><th>Value</th><th>Description</th>
        </tr>
    </thead>
    <tbody>'
        .$rows
        .'
    </tbody>
</table>

<h3>See Also</h3>
<p>
    <li><a href="http://rtfm.modx.com/revolution/2.x/developing-in-modx/advanced-development/modx-services/modmail">MODX modMail</a></li>
    <li><a href="https://support.google.com/mail/answer/1366858?hl=en&ctx=mail&expand=5">Why messages are marked as Spam</a></li>
</p>
</div>';


$uniqid = uniqid();
$chunk = $modx->newObject('modChunk', array('name' => "{tmp}-{$uniqid}"));
$chunk->setCacheable(false);
$msg = $chunk->process($p, $msg);


$modx->getService('mail', $p['service']);
$modx->mail->set(modMail::MAIL_BODY, $msg);
$modx->mail->set(modMail::MAIL_FROM, $p['from']);
$modx->mail->set(modMail::MAIL_FROM_NAME, $p['fromname']);
$modx->mail->set(modMail::MAIL_SUBJECT, $p['subject']);
$modx->mail->address('to', $p['to']);
$modx->mail->address('reply-to', $p['replyto']);
$modx->mail->setHTML($p['html']);
if (!$modx->mail->send()) {
    return sprintf($errorTpl,'An error occurred while trying to send the email: ' . $modx->mail->mailer->ErrorInfo);
}
$modx->mail->reset();


// Success?
$uniqid = uniqid();
$chunk = $modx->newObject('modChunk', array('name' => "{tmp}-{$uniqid}"));
$chunk->setCacheable(false);
$output = $chunk->process($scriptProperties, $infoTpl);

$output = sprintf($successTpl,'[testEmail]: We think your email went out correctly.  This is no guarantee of success, but we did not see any obvious errors at the system level.  Check to see if the message was delivered, and be sure to check your Spam filters.') . $output;
return $output;

/*EOF*/