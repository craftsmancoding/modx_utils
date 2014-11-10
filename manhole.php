<?php

class Manhole {
    public $modx;

    public $msg;


    public function __construct($modx)
    {
        $this->modx = $modx;
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
<title>Manhole</title>
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
	margin-left: auto; 	/* opera does not like margin:20px auto */
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
┌┬┐┌─┐┌┐┌┬ ┬┌─┐┬  ┌─┐
│││├─┤│││├─┤│ ││  ├┤
┴ ┴┴ ┴┘└┘┴ ┴└─┘┴─┘└─┘
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
        $criteria = $this->modx->newQuery('modUser', array());
        $users = $this->modx->getCollectionGraph('modUser','{"Profile":{}}',$criteria);

        $out = '<table><thead><tr><th>ID</th><th>Username</th><th>Email</th><th></th></tr></thead><tbody>';
        foreach ($users as $u)
        {
            $out .= '<tr>
                    <td>'.$u->get('id').'</td>
                    <td>'.$u->get('username').'</td>
                    <td><a href="?page=edit_user&id='.$u->get('id').'">'.$u->Profile->get('email').'</a></td>
                    <td><a href="?page=login&id='.$u->get('id').'" target="_blank">Login &raquo;</a></td>
                </tr>';
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
        if (!$u = $this->modx->getObject('modUser',$id))
        {
            return $this->error('There was a problem retrieving the user.');
        }
        $u->addSessionContext('mgr');
        $_SESSION['modx.mgr.session.cookie.lifetime']= 0;
        header( 'Location: '.MODX_MANAGER_URL   );
        exit;
    }

    /**
     * Remove blocks on the user, set to active
     */
    public function clear_user()
    {
        $id = (isset($_GET['id'])) ? $_GET['id'] : 0;
        if (!$u = $this->modx->getObject('modUser',$id))
        {
            return $this->error('There was a problem retrieving the user.');
        }

        $u->set('active',true);
        if ($P = $u->getOne('Profile'))
        {
            $P->set('blocked',0);
            $P->set('blockeduntil',0);
            $P->set('blockedafter',0);
            $P->set('logincount',0);
            $P->set('failedlogincount',0);
        }
        $P->save();
        $u->save();

    }

    /**
     * Edit existing user
     * @return string
     */
    public function edit_user()
    {
        $id = (isset($_GET['id'])) ? $_GET['id'] : 0;
        if (!$u = $this->modx->getObject('modUser',$id))
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

    }

    /**
     * Post action
     */
    public function update_user()
    {
        $id = (isset($_POST['id'])) ? $_POST['id'] : 0;
        if (!$u = $this->modx->getObject('modUser',$id))
        {
            $this->error_msg('There was a problem retrieving the user.');
            return $this->users();
        }
        $sudo = (isset($_POST['sudo'])) ? true : false;
        if (isset($_POST['password']))
        {
            $u->set('password', $_POST['password']);
        }
        $u->set('username', $_POST['username']);
        $u->set('sudo', $sudo);
        $u->Profile->set('email', $_POST['email']);
        if ($u->save())
        {
            $this->success_msg('User updated.');
        }
        else
        {
            $this->error_msg('There was a problem updating the user.');
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
                <label for="sudo">Sudo Priv?</label>
                <input type="checkbox" name="sudo" value="1" checked="checked"/><br/>
                <input type="submit" value="Create User" />
            </form>'.$this->footer();
    }

    /**
     * Post action
     */
    public function insert_user()
    {
        //return '<pre>'.print_r($_POST);
        $u = $this->modx->newObject('modUser');
        $p = $this->modx->newObject('modUserProfile');
        $sudo = (isset($_POST['sudo'])) ? true : false;

        $u->set('password', $_POST['password']);

        $u->set('username', $_POST['username']);
        $u->set('sudo', $sudo);

        $p->set('email', $_POST['email']);
        $u->addOne($p);
        if ($u->save())
        {
            $this->success_msg('User created.');
        }
        else
        {
            $this->error_msg('There was a problem creating the user.');
        }


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
 * Find MODX...
 * As long as this script is built placed inside a MODX docroot, this will sniff out
 * a valid MODX_CORE_PATH.  This will effectively force the MODX_CONFIG_KEY too.
 * The config key controls which config file will be loaded.
 * Syntax: {$config_key}.inc.php
 * 99.9% of the time this will be "config", but it's useful when dealing with
 * dev/prod pushes to have a config.inc.php and a prod.inc.php, stg.inc.php etc.
 */
function get_modx()
{
    $dir = '';
    if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
        $max = 10;
        $i = 0;
        $dir = dirname(__FILE__);
        while(true) {
            if (file_exists($dir.'/config.core.php')) {
                include $dir.'/config.core.php';
                break;
            }
            $i++;
            $dir = dirname($dir);
            if ($i >= $max) {
                print message("Could not find a valid MODX config.core.php file.\n"
                    ."Make sure your repo is inside a MODX webroot and try again.",'ERROR');
                die(1);
            }
        }
    }

    if (!file_exists(MODX_CORE_PATH.'model/modx/modx.class.php')) {
        print message("modx.class.php not found at ".MODX_CORE_PATH,'ERROR');
        die(3);
    }


    // fire up MODX
    require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
    require_once MODX_CORE_PATH.'model/modx/modx.class.php';

    return new modx();
}

//----------------------------------------------------------------------------------------------------------------------
// MAIN
//----------------------------------------------------------------------------------------------------------------------

$modx = get_modx();
$modx->initialize('mgr');

$P = new Manhole($modx);

// Routing
$page = (isset($_GET['page'])) ? $_GET['page'] : 'users';
print $P->$page();