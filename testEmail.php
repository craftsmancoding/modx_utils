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

$to = $modx->getOption('to',$scriptProperties);
$from = $modx->getOption('from',$scriptProperties, $modx->getOption('emailsender'));
$fromname = $modx->getOption('fromname',$scriptProperties, $modx->getOption('site_name'));
$replyto = $modx->getOption('replyto',$scriptProperties, $modx->getOption('emailsender'));
$subject = $modx->getOption('subject',$scriptProperties, 'Test Email Functionality');
$service = $modx->getOption('service',$scriptProperties, 'mail.modPHPMailer');
$html = $modx->getOption('html',$scriptProperties, true);
$msg = $modx->getOption('msg',$scriptProperties, 'This is a test email from the testEmail Snippet on the [[++site_name]] website. It was sent from [[+from]] to [[+to]].');


// Formatting Strings with inline styling
$errorTpl = '<div style="margin:10px; padding:20px; border:1px solid red; background-color:pink; border-radius: 5px; width:500px;">
    <span style="color:red; font-weight:bold;">Error</span><br />
    <p>%s</p></div>';
$successTpl = '<div style="margin:10px; padding:20px; border:1px solid green; background-color:#00CC66; border-radius: 5px; width:500px;">
		<span style="color:green; font-weight:bold;">Success!</span>
		<p>%s</p></div>';

if (empty($to)) {
    return sprintf($errorTpl,'[testEmail]: The &to parameter is required.');
}

// Our informational Chunk
$infoTpl = '
<div style="margin:10px; padding:20px; border:1px solid gray; border-radius: 5px; width:500px;">
<h3>Script Properties</h3>
<pre>'.
print_r($scriptProperties,true)
.'</pre>
<h3>Email Related System Settings</h3>
<strong>emailsender:</strong> [[++email_sender]] <em>([[%setting_email_sender_desc]])</em><br/>
<strong>allow_multiple_emails</strong> [[++allow_multiple_emails]] <em>([[%setting_allow_multiple_emails_desc]])</em><br/>
<strong>mail_smtp_auth</strong> [[++mail_smtp_auth]] <em>([[%setting_mail_smtp_auth_desc]])</em><br/>
<strong>mail_smtp_helo</strong> [[++mail_smtp_helo]] <em>([[%setting_mail_smtp_helo_desc]])</em><br/>
<strong>mail_smtp_hosts</strong> [[++mail_smtp_hosts]] <em>([[%setting_mail_smtp_hosts_desc]])</em><br/>
<strong>mail_smtp_keepalive</strong> [[++mail_smtp_keepalive]]<em>([[%setting_mail_smtp_keepalive_desc]])</em><br/>
<strong>mail_smtp_pass</strong> [[++mail_smtp_pass]]<em>([[%setting_mail_smtp_pass_desc]])</em><br/>
<strong>mail_smtp_port</strong> [[++mail_smtp_port]]<em>([[%setting_mail_smtp_port_desc]])</em><br/>
<strong>mail_smtp_prefix</strong> [[++mail_smtp_prefix]]<em>([[%setting_mail_smtp_prefix_desc]])</em><br/>
<strong>mail_smtp_single_to</strong> [[++mail_smtp_single_to]]<em>([[%setting_mail_smtp_single_to_desc]])</em><br/>
<strong>mail_smtp_timeout</strong> [[++mail_smtp_timeout]]<em>([[%setting_mail_smtp_timeout_desc]])</em><br/>
<strong>mail_smtp_user</strong> [[++mail_smtp_user]]<em>([[%setting_mail_smtp_user_desc]])</em><br/>
<strong>mail_use_smtp</strong> [[++mail_use_smtp]]<em>([[%setting_mail_use_smtp_desc]])</em><br/>


<h3>See Also</h3>
<p>
    <li>
        <a href="http://rtfm.modx.com/revolution/2.x/developing-in-modx/advanced-development/modx-services/modmail">MODX modMail</a>
    </li>
</p>
</div>';


$uniqid = uniqid();
$chunk = $modx->newObject('modChunk', array('name' => "{tmp}-{$uniqid}"));
$chunk->setCacheable(false);
$msg = $chunk->process($scriptProperties, $msg);


$modx->getService('mail', $service);
$modx->mail->set(modMail::MAIL_BODY, $msg);
$modx->mail->set(modMail::MAIL_FROM, $from);
$modx->mail->set(modMail::MAIL_FROM_NAME, $fromname);
$modx->mail->set(modMail::MAIL_SUBJECT, $subject);
$modx->mail->address('to', $to);
$modx->mail->address('reply-to', $replyto);
$modx->mail->setHTML($html);
if (!$modx->mail->send()) {
    return sprintf($errorTpl,'An error occurred while trying to send the email: ' . $modx->mail->mailer->ErrorInfo);
}
$modx->mail->reset();


// Success?
$uniqid = uniqid();
$chunk = $modx->newObject('modChunk', array('name' => "{tmp}-{$uniqid}"));
$chunk->setCacheable(false);
$output = $chunk->process($scriptProperties, $infoTpl);

$output = sprintf($successTpl,'[testEmail]: We think your email went out correctly.  This is no guarantee of success, but we did not see any obvious errors at the system level.  Check to see if the message was delivered!') . $output;
return $output;

/*EOF*/