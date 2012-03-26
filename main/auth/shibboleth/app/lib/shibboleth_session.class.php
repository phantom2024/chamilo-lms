<?php

/**
 * A Chamilo user session. Used as there is no session object so far provided by the core API.
 * Should be moved to the core library.Prefixed by Shibboleth to avoid name clashes.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
class ShibbolethSession
{

    /**
     * @return ShibbolethSession
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    function is_logged_in()
    {
        return isset($_SESSION['_user']['user_id']);
    }

    function user()
    {
        return $_SESSION['_user'];
    }

    function logout()
    {
        $_SESSION['_user'] = array();
    }

    /**
     * Create a Shibboleth session for the user ID
     *
     * @param  string $_uid - The user ID
     * @return $_user (array) - The user infos array created when the user logs in
     */
    function login($_uid)
    {
        $user = User::store()->get_by_user_id($_uid);
        if (empty($user))
        {
            return;
        }

        api_session_register('_uid');

        global $_user;
        $_user = (array)$user;

        $_SESSION['_user'] = $_user;
        $_SESSION['_user']['user_id'] = $_uid;
        $_SESSION['noredirection'] = true;

        //used in 'init_local.inc.php'
        $loginFailed = false;
        $uidReset = true;

        $gidReset = true;
        $cidReset = true;

        $mainDbName = Database :: get_main_database();
        $includePath = api_get_path(INCLUDE_PATH);

        require("$includePath/local.inc.php");

        event_login();

        return $_user;
    }

}