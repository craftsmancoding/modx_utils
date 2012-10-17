<?php
/**
 * SUMMARY:
 * This script will reset the password and email for a MODX user for websites running MODX Revolution 
 * (2.2.0 or greater). It was developed as a back-door utility for getting into sites where I had 
 * been inadvertently locked out.  THIS SCRIPT IS INTENDED TO BE USED FOR PEACEFUL
 * RESOPONSIBLE PURPOSES BUT IT CAN BE USED MALICIOUSLY!!! BEWARE!!!
 * 
 * USAGE:
 * 1. Upload this script to the Revo website where you need to reset the password, e.g. to the docroot.
 * 2. Edit the configuration details, including the $username, $password, and $email.  The username
 *	is what uniquely identifies the user (not the email).  If you put this script somewhere other than 
 *	alongside the MODX index.php file, you must also update the $path_to_docroot.
 * 3. Run the script, e.g. by visiting it in a browser: http://yoursite.com/reset_password.php
 * 4. After you verify that the new user has been created, delete this script from the site.
 *
 * USE THIS SCRIPT AT YOUR OWN RISK!!!
 *
 * See also: 
 * http://rtfm.modx.com/display/revolution20/Resetting+a+User+Password+Manually
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 * http://craftsmancoding.com/
 */
//------------------------------------------------------------------------------
// CONFIGURATION
//------------------------------------------------------------------------------
// Reset the password and email of an existing user
// and ensure they are a member of the specified group
$username = '';
$password = '';
$email = '';
$user_group = 1; // 1 for Administrator
$user_role = 2; // 2 for Super User
// Leave blank if the script is placed inside the docroot
$path_to_docroot = '';

//------------------------------------------------------------------------------
// DO NOT EDIT BELOW THIS LINE
//------------------------------------------------------------------------------
if (empty($username) || empty($password) || empty($email)) {
	print 'ERROR: Missing criteria.';
	exit;
}
define('MODX_API_MODE', true);
// Full path to the MODX index.php file
require_once($path_to_docroot.'index.php');

if (empty($username) || empty($password) || empty($email)) {
    print 'ERROR: Missing criteria.';
    exit;
}
 
if (!class_exists('modX') || !defined('MODX_CORE_PATH')) {
	print 'modX not found.';
	exit; 
}
 
$modx= new modX();
$modx->initialize('mgr');
if (!$modx) {
	print 'MODX not initialized correctly.';
	exit;
}

$query = $modx->newQuery('modUser');
$query->where( array('username'=>$username) );
$user = $modx->getObject('modUser', $query);
//$user = $modx->getObject('modUser', 6486);
if (!$user) {
    print "ERROR: No user with username $username";
    exit;
}
 
// Set user details.
$user->set('username',$username);
$user->set('active',1);
$user->set('password', $password);
$user->Profile->set('email', $email);
$user->Profile->set('blocked', 0);
$user->Profile->set('blockeduntil', 0);
$user->Profile->set('blockedafter', 0);
 
// Verify the user is a member of specified User Group
$is_member = false;
if (!empty($user->UserGroupMembers)) {
    foreach ($user->UserGroupMembers as $UserGroupMembers) {
        if ($UserGroupMembers->get('user_group') == $user_group) {
            $is_member = true;
            break;          
        }
    }
}
// Add the User to the User Group if he is not a member
if (!$is_member) {
    // Verify the user group exists
    $UserGroup = $modx->getObject('modUserGroup', $user_group);
    if (!$UserGroup) {
        print "ERROR: User Group $user_group does not exist.";
        exit;
    }
 
    $Member = $modx->newObject('modUserGroupMember');
    $Member->set('user_group', $user_group); 
    $Member->set('member', $user->get('id'));
    // Super User = role 2
    $Member->set('role', $user_role); 
    $Member->set('rank', 0);
    $user->addOne($Member,'UserGroupMembers');
}
 
/* save user */
if (!$user->save()) {
    print 'ERROR: Could not save user.';
    exit;
}
 
print "SUCCESS: User $username updated.";
 
/*EOF*/