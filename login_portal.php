<?php
/**
 * SUMMARY:
 * This script will create a login flow with all necessary components on your MODX Revolution site. 
 * 
 * USAGE:
 * 1. Upload this script to the Revo website where you want to install the login portal, e.g. to the docroot.
 * 2. Edit the configuration details.
 *  If you put this script somewhere other than alongside the MODX index.php file, you must also update the 
 * 	$path_to_docroot.
 * 3. Run the script, e.g. by visiting it in a browser: http://yoursite.com/login_portal.php
 * 4. After you verify that the login flow has been created, delete this script from the site.
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
// Usergroups / Resource groups will be created if they don't already exist
$resource_group = 'Members';
$user_group = 'Members - Paid';
$template = 1;
$create_container = true;
$create_test_users = true;

// Define one ore more test users here using the format username:password
$test_users = array('testuser:abcd1234');

// Leave blank if the script is placed inside the docroot
$path_to_docroot = '';

//------------------------------------------------------------------------------
// DO NOT EDIT BELOW THIS LINE
//------------------------------------------------------------------------------
@ini_set('memory_limit', -1);
if (empty($resource_group) || empty($user_group)) {
	print 'ERROR: Missing criteria.';
	exit;
}

define('MODX_API_MODE', true);

// Full path to the index
require_once($path_to_docroot.'index.php');
$modx= new modX();
$modx->initialize('mgr');

$parent_id = 0;
if ($create_container) {
    $container = $modx->getObject('modResource', array('pagetitle'=>'Dashboard'));
    if (!$container) {
        $container = $modx->newObject('modResource');
        $container->set('pagetitle', 'Dashboard');
        $container->set('alias', 'dashboard');
        $container->set('published',1);
        $container->set('content','');
        $container->set('parent', 0);
        $container->set('isfolder',1);
        $container->save();
    }
    $parent_id = $container->get('id');    
}

// Create User Group and Resource Group
$usergroup = $modx->getObject('modUserGroup', array('name'=>$user_group));
if (!$usergroup) {
    print 'Creating User Group...<br/>';
    $usergroup = $modx->newObject('modUserGroup', array('name'=>$user_group,'description'=>'Members'));
    $usergroup->save();
}
$resourcegroup = $modx->getObject('modResourceGroup', array('name'=>$resource_group));
if (!$resourcegroup) {
    print 'Creating Resource Group...<br/>';
    $resourcegroup = $modx->newObject('modResourceGroup', array('name'=>$user_group));
    $resourcegroup->save();
}

// Verfiy UserGroup --> ResourceGroup permissions
//modAccessContext
$AccessContext = $modx->getObject('modAccessContext', array('target'=>'web','policy'=>4));
if (!$AccessContext) {
    $AccessContext = $modx->newObject('modAccessContext');
    $AccessContext->set('target','web');
    $AccessContext->set('principal_class','modUserGroup');
    $AccessContext->set('principal',1);
    $AccessContext->set('authority',9999);
    $AccessContext->set('policy',4); // 4: load,list,and view
    $AccessContext->save();
}

// Create Pages for Login Flow
// See http://rtfm.modx.com/extras/revo/login/login.tutorials/login.user-profiles

//! Come Again Soon (5) 
// the page displayed upon successful logout
$page5 = $modx->getObject('modResource', array('pagetitle'=>'Thank You'));
if (!$page5) {
    $page5 = $modx->newObject('modResource');
    $page5->set('pagetitle', 'Thank You');
    $page5->set('alias', 'thank-you');
    $page5->set('published',1);
    $page5->set('content','<p>Thank you for visiting! Come again soon.</p>');
    $page5->set('parent', $parent_id);
    $page5->set('template',$template);
    $page5->save();
}

//! Request Pending (7) 
// notifies the user that the user's request has been received
$page7 = $modx->getObject('modResource', array('pagetitle'=>'Request Pending'));
if (!$page7) {
    $page7 = $modx->newObject('modResource');
    $page7->set('pagetitle', 'Request Pending');
    $page7->set('alias', 'request-pending');
    $page7->set('hidemenu',1);
    $page7->set('published',1);
    $page7->set('content','<p>Thank you for your interest in our site! Check your email for an activation link.  
You will need to click this link before you can log into our site.</p>');
    $page7->set('parent', $parent_id);
    $page7->set('template',1);
    $page7->save();
}


//! Home Page (9) 
// Where you Redirect after a user has been confirmed
$page9 = $modx->getObject('modResource', array('pagetitle'=>'Home Page'));
if (!$page9) {
    $page9 = $modx->newObject('modResource');
    $page9->set('pagetitle', 'Home Page');
    $page9->set('alias', 'home');
    $page9->set('hidemenu',1);
    $page9->set('published',1);
    $page9->set('content','');
    $page9->set('parent', $parent_id);
    $page9->set('template',1);    
    $page9->save();
}

//! Membership Confirmation Handler (8)
// the hidden page that will actually register the user
$page8 = $modx->getObject('modResource', array('pagetitle'=>'Membership Confirmation Handler'));
if (!$page8) {
    $page8 = $modx->newObject('modResource');
    $page8->set('pagetitle', 'Membership Confirmation Handler');
    $page8->set('alias', 'membership-confirmation-handler');
    $page8->set('hidemenu',1);
    $page8->set('published',1);
    $page8->set('content','[[ConfirmRegister? &redirectTo=`'.$page9->get('id').'`]]');
    $page8->set('parent', $parent_id);
    $page8->set('template',1);
    $page8->save();
}


//! Request Membership (6) 
// the page where users can request membership, i.e. the "Become a Member" page.
$page6 = $modx->getObject('modResource', array('pagetitle'=>'Request Membership'));
if (!$page6) {
    $page6 = $modx->newObject('modResource');
    $page6->set('pagetitle', 'Request Membership');
    $page6->set('alias', 'request-membership');
    $page6->set('hidemenu',1);
    $page6->set('published',1);
    $page6->set('content','[[!Register?
            &activationResourceId=`'.$page8->get('id').'`
            &activationEmailSubject=`Thanks for Registering!`
            &submittedResourceId=`'.$page7->get('id').'`
            &usergroups=`'.$user_group.'`
        ]]
<div class="register">
    <div class="registerMessage">[[+error.message]]</div>
       
    <form class="form" action="[[~[[*id]]]]" method="post">
        <input type="hidden" name="nospam:blank" value="" />
           
        <label for="username">[[%register.username? &namespace=`login` &topic=`register`]]
            <span class="error">[[+error.username]]</span>
        </label>
        <input type="text" name="username:required:minLength=6" id="username" value="[[+username]]" />
           
        <label for="password">[[%register.password]]
            <span class="error">[[+error.password]]</span>
        </label>
        <input type="password" name="password:required:minLength=6" id="password" value="[[+password]]" />
           
        <label for="password_confirm">[[%register.password_confirm]]
            <span class="error">[[+error.password_confirm]]</span>
        </label>
        <input type="password" name="password_confirm:password_confirm=`password`" id="password_confirm" value="[[+password_confirm]]" />
           
        <label for="fullname">[[%register.fullname]]
            <span class="error">[[+error.fullname]]</span>
        </label>
        <input type="text" name="fullname:required" id="fullname" value="[[+fullname]]" />
           
        <label for="email">[[%register.email]]
            <span class="error">[[+error.email]]</span>
        </label>
        <input type="text" name="email:email" id="email" value="[[+email]]" />
                  
        <br class="clear" />
           
        <div class="form-buttons">
            <input type="submit" name="registerbtn" value="Register" />
        </div>
    </form>
</div>        
        
        ');
    $page6->set('parent', $parent_id);
    $page6->set('template',1);
    $page6->save();
}


//! Change Password (12) 
// where the user can change their password.
$page12 = $modx->getObject('modResource', array('pagetitle'=>'Change Password'));
if (!$page12) {
    $page12 = $modx->newObject('modResource');
    $page12->set('pagetitle', 'Change Password');
    $page12->set('alias', 'change-password');
    $page12->set('hidemenu',1);
    $page12->set('published',1);
    $page12->set('content','
[[!ChangePassword?
   &submitVar=`change-password`
   &placeholderPrefix=``
   &validateOldPassword=`1`
   &validate=`nospam:blank`
   &reloadOnSuccess=`0`
   &successMessage=`Your password has been updated!`
]]
<div>[[!+successMessage]]</div>
<div class="updprof-error">[[!+error_message]]</div>
<form class="form" action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="nospam" value="" />
    <div class="ff">
        <label for="password_old">Old Password
            <span class="error">[[!+error.password_old]]</span>
        </label>
        <input type="password" name="password_old" id="password_old" value="[[+password_old]]" />
    </div>
    <div class="ff">
        <label for="password_new">New Password
            <span class="error">[[!+error.password_new]]</span>
        </label>
        <input type="password" name="password_new" id="password_new" value="[[+password_new]]" />
    </div>
    <div class="ff">
        <label for="password_new_confirm">Confirm New Password
            <span class="error">[[!+error.password_new_confirm]]</span>
        </label>
        <input type="password" name="password_new_confirm" id="password_new_confirm" value="[[+password_new_confirm]]" />
    </div>
    <div class="ff">
        <input type="submit" name="change-password" value="Change Password" />
    </div>
</form>');
    $page12->set('parent', $parent_id);
    $page12->set('template',1);
    $page12->save();
}




//! Update Profile (10) 
// where the user can edit their profile
$page10 = $modx->getObject('modResource', array('pagetitle'=>'Edit Profile'));
if (!$page10) {
    $page10 = $modx->newObject('modResource');
    $page10->set('pagetitle', 'Edit Profile');
    $page10->set('alias', 'edit-profile');
    $page10->set('hidemenu',1);
    $page10->set('published',1);
    $page10->set('content','[[!UpdateProfile? &useExtended=`0`]]
  
<div class="update-profile">
    <div class="updprof-error">[[+error.message]]</div>
    [[+login.update_success:if=`[[+login.update_success]]`:is=`1`:then=`[[%login.profile_updated? &namespace=`login` &topic=`updateprofile`]]`]]
  
    <form class="form" action="[[~[[*id]]]]" method="post">
        <input type="hidden" name="nospam:blank" value="" />
  
        <label for="fullname">[[!%login.fullname? &namespace=`login` &topic=`updateprofile`]]
            <span class="error">[[+error.fullname]]</span>
        </label>
        <input type="text" name="fullname" id="fullname" value="[[+fullname]]" />
  
        <label for="email">[[!%login.email]]
            <span class="error">[[+error.email]]</span>
        </label>
        <input type="text" name="email:required:email" id="email" value="[[+email]]" />
  
        <br class="clear" />
  
        <div class="form-buttons">
            <input type="submit" name="login-updprof-btn" value="[[!%login.update_profile]]" />
        </div>
    </form>
</div>
<p><a href="[[~'.$page12->get('id').']]">Change Password</a></p>
</p>
');
    $page10->set('parent', $parent_id);
    $page10->set('template',1);
    $page10->save();
}
$rg = $modx->getObject('modResourceGroupResource', array('document'=>$page10->get('id'),'document_group'=>$resourcegroup->get('id')));
if (!$rg) {
    $rg = $modx->newObject('modResourceGroupResource', array('document'=>$page10->get('id'),'document_group'=>$resourcegroup->get('id')));
    $rg->save();
}

//! View Profile (11) 
// where the user can view their profile
$page11 = $modx->getObject('modResource', array('pagetitle'=>'View Profile'));
if (!$page11) {
    $page11 = $modx->newObject('modResource');
    $page11->set('pagetitle', 'View Profile');
    $page11->set('alias', 'view-profile');
    $page11->set('hidemenu',1);
    $page11->set('published',1);
    $page11->set('content','[[!Profile]]
  
<p>Username: [[+username]]</p>
<p>Full Name: [[+fullname]]</p>
<p>Email: [[+email]]</p>
 
<p><a href="[[~'.$page10->get('id').']]">Edit</a></p>');
    $page11->set('parent', $parent_id);
    $page11->set('template',1);
    $page11->save();
}
$rg = $modx->getObject('modResourceGroupResource', array('document'=>$page11->get('id'),'document_group'=>$resourcegroup->get('id')));
if (!$rg) {
    $rg = $modx->newObject('modResourceGroupResource', array('document'=>$page11->get('id'),'document_group'=>$resourcegroup->get('id')));
    $rg->save();
}


//! Members Home Page (4) 
// the secret clubhouse, available only to valid members
$page4 = $modx->getObject('modResource', array('pagetitle'=>'Member Home'));
if (!$page4) {
    $page4 = $modx->newObject('modResource');
    $page4->set('pagetitle', 'Member Home');
    $page4->set('alias', 'members');
    $page4->set('published',1);
    $page4->set('content','<p>Welcome members!</p><p><a href="[[~'.$page10->get('id').']]">Edit Profile</a>');
    $page4->set('parent', $parent_id);
    $page4->set('template',$template);
    $page4->save();
}
$rg = $modx->getObject('modResourceGroupResource', array('document'=>$page4->get('id'),'document_group'=>$resourcegroup->get('id')));
if (!$rg) {
    $rg = $modx->newObject('modResourceGroupResource', array('document'=>$page4->get('id'),'document_group'=>$resourcegroup->get('id')));
    $rg->save();
}


//! Login Page (1) 
// the page containing your login form
$page1 = $modx->getObject('modResource', array('pagetitle'=>'Login'));
if (!$page1) {
    $page1 = $modx->newObject('modResource');
    $page1->set('pagetitle', 'Login');
    $page1->set('alias', 'login');
    $page1->set('published',1);
    $page1->set('content','[[!Login? &loginResourceId=`'.$page4->get('id').'` &logoutResourceId=`'.$page5->get('id').'`]]');
    $page1->set('parent', $parent_id);
    $page1->set('template',$template);    
    $page1->save();
}
//! Logout Link (13)
// [[~1? &service=`logout`]]
$page13 = $modx->getObject('modResource', array('pagetitle'=>'Logout'));
if (!$page13) {
    $page13 = $modx->newObject('modResource');
    $page13->set('pagetitle', 'Logout');
    $page13->set('alias', 'logout');
    $page13->set('class_key','modWebLink');    
    $page13->set('published',1);
    $page13->set('content','[[~'.$page1->get('id').'? &service=`logout`]]');
    $page13->set('parent', $parent_id);
    $page13->set('template',0);
    $page13->save();
}

//! Reset Password Handler (3) 
// the hidden page that will actually do the resetting of the password
$page3 = $modx->getObject('modResource', array('pagetitle'=>'Reset Password Handler'));
if (!$page3) {
    $page3 = $modx->newObject('modResource');
    $page3->set('pagetitle', 'Reset Password Handler');
    $page3->set('alias', 'reset-password-handler');
    $page3->set('hidemenu',1);
    $page3->set('published',1);
    $page3->set('content','[[!ResetPassword? &loginResourceId=`'.$page1->get('id').'`]]');
    $page3->set('parent', $parent_id);
    $page3->set('template',1);
    $page3->save();
}


//! Forgot Password (2) 
// the page where users can go when they forgot their password
$page2 = $modx->getObject('modResource', array('pagetitle'=>'Forgot Password'));
if (!$page2) {
    $page2 = $modx->newObject('modResource');
    $page2->set('pagetitle', 'Forgot Password');
    $page2->set('alias', 'forgot-password');
    $page2->set('hidemenu',1);
    $page2->set('published',1);
    $page2->set('content','[[!ForgotPassword? &resetResourceId=`'.$page3->get('id').'`]]');
    $page2->set('parent', $parent_id);
    $page2->set('template',1);
    $page2->save();
}




// Create Test Users
if ($create_test_users) {
    foreach ($test_users as $u) {
        list($username, $password) = explode(':', $u);
        $email = $username.'@test.com';
        
        $user = $modx->getObject('modUser', array('username'=>$username));
        if (!$user) {
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
            }
        }
        
        // Add User to a User Group
        $Member = $modx->newObject('modUserGroupMember');
        $Member->set('user_group', $usergroup->get('id')); 
        $Member->set('member', $user->get('id'));
        // Grant the user a role within that group
        $Member->set('role', 1); 
        $Member->set('rank', 0);
        
        if (!$Member->save()) {
        	print 'ERROR: Could not add User to User Group';
        	exit;
        }
    }
}

// Flush Permissions ??

// Clear Cache
$modx->query("TRUNCATE TABLE ".$modx->getTableName("modSession"));
$modx->cacheManager->refresh();

$login_id = $page1->get('id');
$url = $modx->makeUrl($login_id,'','','full');
print 'SUCCESS.  You can log in at <a href="'.$url.'">Login Page</a>';

/*EOF*/