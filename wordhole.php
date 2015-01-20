<?php

class Wordhole {
//    public $modx;

    public $msg;


    public function __construct()
    {
        //$this->modx = $modx;
    }

    /**
     * 404
     * @param $name
     * @param $args
     *
     * @return string
     */
    public function __call($name,$args)
    {
        $this->error_msg('Page not Found.');
        return $this->users();
    }
    public function header($title,$msg='')
    {

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Wordhole</title>
<style>
body {
    margin: 0px 0px 0px 0px;
    padding: 0px 0px 0px 0px;
    font-family: verdana, arial, helvetica, sans-serif;
    color: #ccc;
    background-color: #333;
    text-align: center;
/* part 1 of 2 centering hack */
    }
a {
    text-decoration: none;
    color:  #778899;
    outline: none;
    }
a:visited {

    }
a:active {
    color:  white;
    }
a:hover {
    color: white;
    text-decoration: underline;
    }
strong, b {
    font-weight: bold;
    }
p {
    font-size: 12px;
    /* line-height: 22px; */
    margin-top: 20px;
    margin-bottom: 10px;
    }

h1 {
        font-size: 24px;
    /* line-height: 44px; */
    font-weight: bold;
    margin-top: 0;
    margin-bottom: 0;
    }
h2 {
        font-size: 18px;
    /* line-height: 40px; */
    font-weight: bold;
    margin-top: 0;
    margin-bottom: 0;
    }
img {
        border: 0;
    }
.nowrap {
        white-space: nowrap;
    font-size: 10px;
    font-weight: bold;
    margin-top: 0;
    margin-bottom: 0;
/* must be combined with nobr in html for ie5win */
    }
.tiny {
        font-size: 9px;
    line-height: 16px;
    margin-top: 15px;
    margin-bottom: 5px;
    }
#content {
    padding: 10px;
    margin-top: 20px;
    margin-bottom: 20px;
    margin-right: auto;
    margin-left: auto;  /* opera does not like margin:20px auto */
    background: #666;
    border: 2px solid #ccc;
    text-align:left; /* part 2 of 2 centering hack */
    width: 700px; /* ie5win fudge begins */
    voice-family: "\"}\"";
    voice-family:inherit;

    }
pre {

    }
</style>
</head>
<body>
<pre>
 _    _               _ _           _      
| |  | |             | | |         | |     
| |  | | ___  _ __ __| | |__   ___ | | ___ 
| |/\| |/ _ \| \'__/ _` | \'_ \ / _ \| |/ _ \
\  /\  / (_) | | | (_| | | | | (_) | |  __/
 \/  \/ \___/|_|  \__,_|_| |_|\___/|_|\___|
</pre>
<h1>'.$title.'</h1>
<div id="content">' . $this->msg;
    }

    /**
     * @return string
     */
    public function footer()
    {
        return '</div><div><a href="?">Back</a> | <a href="?page=create_user">Create User</a></div></body></html>';
    }


    public static function url($page)
    {

    }
    /**
     *
     * @return string
     */
    public function users()
    {
        

        $users = get_users();

        $out = '<table><thead><tr><th>ID</th><th>Username</th><th>Email</th><th></th></tr></thead><tbody>';
        foreach ($users as $u)
        {
            $out .= '<tr>
                    <td>'.$u->ID.'</td>
                    <td>'.$u->user_login.'</td>';

                $out .='<td><a href="?page=edit_user&id='.$u->ID.'">'.$u->user_email.'</a></td>';

            
            $out .='<td><a href="?page=login&id='.$u->ID.'" target="_blank">Login &raquo;</a></td></tr>';
        }
        $out .= '</tbody></table>';
        // TODO: Pagination!
        return $this->header('Users',$msg) . $out . $this->footer();
    }

    /**
     * Log into the manager as the specified user.
     * @return string
     */
    public function login()
    {
        $id = (isset($_GET['id'])) ? $_GET['id'] : 0;
        wp_set_auth_cookie($id);
        wp_safe_redirect(admin_url());
		exit();
        exit;
    }

    /**
     * Remove blocks on the user, set to active
     */
    public function clear_user()
    {
        $id = (isset($_GET['id'])) ? $_GET['id'] : 0;

    }

    /**
     * Edit existing user
     * @return string
     */
    public function edit_user()
    {
/*
        $id = (isset($_GET['id'])) ? $_GET['id'] : 0;
        if (!$u = 'get-user-here')
        {
            $this->error_msg('There was a problem retrieving the user.');
            return $this->users();
        }

        return $this->header('Edit User').'<form action="?page=update_user" method="post">
                <input type="hidden" name="id" value="'.$u->get('id').'"/>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="'.htmlspecialchars($u->get('username')).'"/><br/>
                <label for="email">Email</label>
                <input type="text" name="email" id="email" value="'.htmlspecialchars($u->Profile->get('email')).'"/><br/>
                <label for="password">Password</label>
                <input type="text" name="password" id="password" placeholder="New Password..." value=""/><br/>
                <label for="sudo">Sudo Priv?</label>
                <input type="checkbox" name="sudo" value="1" checked="checked"/><br/>
                <input type="submit" value="Save User" />
            </form>'.$this->footer();
*/

    }

    /**
     * Post action
     */
    public function update_user()
    {
        $id = (isset($_POST['id'])) ? $_POST['id'] : 0;
        if (!$u = 'get_user')
        {
            $this->error_msg('There was a problem retrieving the user.');
            return $this->users();
        }

        if (isset($_POST['password']))
        {
            
        }

        return $this->users();

    }

    /**
     * Create new User
     */
    public function create_user()
    {
        return $this->header('Create User').'<form action="?page=insert_user" method="post">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value=""/><br/>
                <label for="email">Email</label>
                <input type="text" name="email" id="email" value=""/><br/>
                <label for="password">Password</label>
                <input type="text" name="password" id="password" placeholder="Password..." value=""/><br/>
                <input type="submit" value="Create User" />
            </form>'.$this->footer();
    }

    /**
     * Post action
     */
    public function insert_user()
    {

        return $this->users();

    }


    /**
     * Delete this file: erase tracks
     */
    public function delete_this()
    {

    }

    public function clear_logs()
    {

    }

    public function error($msg)
    {
        return $msg;
    }

    public function error_msg($msg)
    {
        $this->msg = '<span style="color:#C00000;">'.$msg.'</span>';
    }
    public function success_msg($msg)
    {
        $this->msg = '<span style="color:#33CC00;">'.$msg.'</span>';
    }
}

/**
 * Find WordPress
 * As long as this script is built placed inside a WordPress docroot, this will sniff out
 * a valid 
 */
function get_wp()
{
    $dir = '';
    if (!defined('DB_NAME')) {
        $max = 10;
        $i = 0;
        $dir = dirname(__FILE__);
        while(true) {
            if (file_exists($dir.'/wp-config.php')) {
                include $dir.'/wp-config.php';
                break;
            }
            $i++;
            $dir = dirname($dir);
            if ($i >= $max) {
                print "Could not find a valid wp-config.php file.\n"
                    ."Make sure your repo is inside a WordPress webroot and try again.";
                die(1);
            }
        }
    }

}

//----------------------------------------------------------------------------------------------------------------------
// MAIN
//----------------------------------------------------------------------------------------------------------------------

$modx = get_wp();


$P = new Wordhole();

// Routing
$page = (isset($_GET['page'])) ? $_GET['page'] : 'users';
print $P->$page();
