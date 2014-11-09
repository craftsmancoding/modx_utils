<?php

class Manhole {
    public $modx;

    public function __construct($modx)
    {
        $this->modx = $modx;
    }

    public function footer()
    {
        return '</body></html>';
    }

    public function header($title)
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
	font-weight: bold;
	color:  #ccc;
	outline: none;
	}
a:visited {
	color:  #ccc;
	}
a:active {
	color:  #ccc;
	}
a:hover {
	color: #ccc;
	text-decoration: underline;
	}
.ahem {
	display: none;
	}
strong, b {
	font-weight: bold;
	}
p {
	font-size: 12px;
	line-height: 22px;
	margin-top: 20px;
	margin-bottom: 10px;
	}

h1 {
        font-size: 24px;
	line-height: 44px;
	font-weight: bold;
	margin-top: 0;
	margin-bottom: 0;
	}
h2 {
        font-size: 18px;
	line-height: 40px;
	font-weight: bold;
	margin-top: 0;
	margin-bottom: 0;
	}
h3 {
        font-size: 16px;
	line-height: 22px;
	font-weight: bold;
	margin-top: 0;
	margin-bottom: 0;
	}
h4 {
        font-size: 14px;
	line-height: 26px;
	font-weight: bold;
	margin-top: 0;
	margin-bottom: 0;
	}
h5 {
        font-size: 12px;
	line-height: 22px;
	font-weight: bold;
	margin-top: 0;
	margin-bottom: 0;
	}
h6 {
        font-size: 10px;
	line-height: 18px;
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
	border: 5px solid #ccc;
	text-align:left; /* part 2 of 2 centering hack */
	width: 400px; /* ie5win fudge begins */
	voice-family: "\"}\"";
	voice-family:inherit;
	width: 370px;
	}
html>body #content {
width: 370px; /* ie5win fudge ends */
}
pre {
    font-size: 12px;
	line-height: 22px;
	margin-top: 20px;
	margin-bottom: 10px;
	}
</style>
</head>
<body>
<h1>Manhole :: '.$title.'</h1>';
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
                    <td>'.$u->Profile->get('email').'</td>
                    <td><a href="?page=login&id='.$u->get('id').'" target="_blank">Login &raquo;</a></td>
                </tr>';
        }
        $out .= '</tbody></table>';
        return $this->header('Users') . $out . $this->footer();
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

    public function edit_user()
    {

    }

    public function create_user()
    {

    }

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