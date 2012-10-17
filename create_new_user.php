<?php
/**
 * SUMMARY:
 * This script will create a new MODX admin user for websites running MODX Revolution (2.2.0 or greater).
 * It was developed as a back-door utility for getting into sites abandoned by previous developers or 
 * sites where the owners had gotten locked out.  THIS SCRIPT IS INTENDED TO BE USED FOR PEACEFUL
 * RESOPONSIBLE PURPOSES BUT IT CAN BE USED MALICIOUSLY!!! BEWARE!!!
 * 
 * USAGE:
 * 1. Upload this script to the Revo website where you need to add a new user, e.g. to the docroot.
 * 2. Edit the configuration details, including the $username, $password, and $email.  To create 
 * 	an admin user, you can leave the defaults for $user_group (1) and $user_role (2).  If you put
 * 	this script somewhere other than alongside the MODX index.php file, you must also update the 
 * 	$path_to_docroot.
 * 3. Run the script, e.g. by visiting it in a browser: http://yoursite.com/create_new_user.php
 * 4. After you verify that the new user has been created, delete this script from the site.
 *
 * USE THIS SCRIPT AT YOUR OWN RISK!!!
 *
 * AUTHOR:
 * Everett Griffiths (everett@craftsmancoding.com)
 * http://craftsmancoding.com/
 */
 
//------------------------------------------------------------------------------
// CONFIGURATION
//------------------------------------------------------------------------------
// Define the new user you want to add
$username = '';
$password = '';
$email = '';
// User group for the new user (Administrator User Group = 1)
$user_group = 1;
// Role for the new user (Super User Role = 2)
$user_role = 2;
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

// Full path to the index
require_once($path_to_docroot.'index.php');
$modx= new modX();
$modx->initialize('mgr');

$user = $modx->newObject('modUser');
$profile = $modx->newObject('modUserProfile');

$user->set('username',$username);
$user->set('active',1);
$user->set('password', $password);

$profile->set('email', $email);
$profile->set('internalKey',0);
$user->addOne($profile,'Profile');

// save user
if (!$user->save()) {
	print 'ERROR: Could not save user.';
	exit;
}

// Add User to a User Group
$Member = $modx->newObject('modUserGroupMember');
$Member->set('user_group', $user_group); 
$Member->set('member', $user->get('id'));
// Grant the user a role within that group
$Member->set('role', $user_role); 
$Member->set('rank', 0);

if (!$Member->save()) {
	print 'ERROR: Could not add User to User Group';
	exit;
}

print "SUCCESS: User $username added.";

/*EOF*/