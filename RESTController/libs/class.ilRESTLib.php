<?php
require_once "./Services/User/classes/class.ilObjUser.php";
require_once "./Services/AccessControl/classes/class.ilRbacReview.php";

class ilRESTLib {

    /**
     * Initialize global instance
     *
     * Note: Taken from Servcies/Init/classes/class.ilInitialization.php
     *
     * @param string $a_name
     * @param string $a_class
     * @param string $a_source_file
     */
    static public function initGlobal($a_name, $a_class, $a_source_file = null)
    {
        if($a_source_file)
        {
            include_once $a_source_file;
            $GLOBALS[$a_name] = new $a_class;
        }
        else
        {
            $GLOBALS[$a_name] = $a_class;
        }
    }

    /**
     * Checks if a user with a given login name owns the administration role.
     * @param $login
     * @return mixed
     */
    public static function isAdminByUsername($login)
    {
        $a_id = ilObjUser::searchUsers($login, 1, true);

        if (count($a_id) > 0) {
            return self::isAdmin($a_id[0]);
        } else {
            return false;
        }

    }

    /**
     * Checks if a user with a usr_id owns the administration role.
     * @param $usr_id
     * @return bool
     */
    public static function isAdmin($usr_id)
    {
        $rbacreview = new ilRbacReview();
        $is_admin = $rbacreview->isAssigned($usr_id,2);
        return $is_admin;
    }

    /**
     * Determines the ILIAS object id (obj_id) given a ref_id
     *
     * @param $ref_id
     * @return mixed
     */
    static public function refid_to_objid($ref_id)
    {
        global $ilDB;
        $query="SELECT obj_id FROM object_reference WHERE object_reference.ref_id=".$ref_id;

        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);
        /* while($row = $ilDB->fetchObject($res))
         {
             $logins[] = $row->login;
         }
         return $logins ? $logins : array();

         //$result = mysql_query($querytext);
         $row = mysql_fetch_assoc($res);*/
        return $row['obj_id'];

    }

    /**
     * Determines the first (among potential many) ref_id's that are associated with
     * an ILIAS object identified by an obj_id.
     *
     * @param $obj_id
     * @return mixed
     */
    static public function objid_to_refid($obj_id)
    {
        global $ilDB;
        $query="SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);
        return $row['ref_id'];
    }

    /**
     * Determines all ref_ids that are associated with a particular ILIAS object
     * identified by its obj_id.
     *
     * @param $obj_id
     * @return array
     */
    static public function objid_to_refids($obj_id)
    {
        global $ilDB;
        $res = array();
        $query="SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
        $set = $ilDB->query($query);

        while($row = $ilDB->fetchAssoc($set))
        {
            $res[] = $row['ref_id'];
        }
        return $res;
    }

    /**
     * Given a user id, this function returns the ilias login name of a user.
     * @param $user_id
     * @return string
     */
    static public function userIdtoLogin($user_id)
    {
        global $ilDB;
        $query = "SELECT login FROM usr_data WHERE usr_id=\"".$user_id."\"";
        $set = $ilDB->query($query);
        $ret = $ilDB->fetchAssoc($set);
        if ($ret) {
            return $ret['login'];
        }
        return "User unknown";
    }

    /**
     * Given a user name, this function returns its ilias user_id.
     * @param login - user_name
     * @return user_id
     */
    static public function loginToUserId($login)
    {
        global $ilDB;
        $query = "SELECT usr_id FROM usr_data WHERE login=\"".$login."\"";
        $set = $ilDB->query($query);
        $ret = $ilDB->fetchAssoc($set);
        if ($ret) {
            return $ret['usr_id'];
        }
    }

    /**
     *  Sets up some frequently needed global variables.
     */
    static public function initDefaultRESTGlobals()
    {
        define("DEBUG", FALSE);
        define("IL_VIRUS_SCANNER", "None");
        // The following constants are normally set by class.ilInitialisation.php->initClientInitFile()
        define ("MAXLENGTH_OBJ_TITLE",125);
        define ("MAXLENGTH_OBJ_DESC",123);

        require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
        require_once "./Modules/File/classes/class.ilObjFile.php";
        //require_once "./Services/User/classes/class.ilObjUser.php";
        require_once("./Services/Xml/classes/class.ilSaxParser.php");

        $lang = "en";
        require_once "./Services/Language/classes/class.ilLanguage.php";
        $lng = new ilLanguage($lang);
        $lng->loadLanguageModule("init");
        self::initGlobal('lng', $lng);

        self::initGlobal("ilias", "ILIAS", "./Services/Init/classes/class.ilias.php");
        self::initGlobal("ilPluginAdmin", "ilPluginAdmin","./Services/Component/classes/class.ilPluginAdmin.php");
        self::initGlobal("objDefinition", "ilObjectDefinition","./Services/Object/classes/class.ilObjectDefinition.php");
        self::initGlobal("ilAppEventHandler", "ilAppEventHandler","./Services/EventHandling/classes/class.ilAppEventHandler.php");
        self::initGlobal("ilObjDataCache", "ilObjectDataCache","./Services/Object/classes/class.ilObjectDataCache.php");
        //self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global $lng, $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilAppEventHandler, $ilObjDataCache, $ilUser;

    }

    /**
     * Taken from class.ilInitialization.php. Since the function is protected there we
     * needed to replicated here.
     * $ilAccess and $rbac... initialisation
     */
    public static function initAccessHandling()
    {
        self::initGlobal("rbacreview", "ilRbacReview",
            "./Services/AccessControl/classes/class.ilRbacReview.php");

        require_once "./Services/AccessControl/classes/class.ilRbacSystem.php";
        $rbacsystem = ilRbacSystem::getInstance();
        self::initGlobal("rbacsystem", $rbacsystem);

        self::initGlobal("rbacadmin", "ilRbacAdmin",
            "./Services/AccessControl/classes/class.ilRbacAdmin.php");

        self::initGlobal("ilAccess", "ilAccessHandler",
            "./Services/AccessControl/classes/class.ilAccessHandler.php");

        require_once "./Services/AccessControl/classes/class.ilConditionHandler.php";
    }

    /**
     * Init Settings
     * Note: Taken from Servcies/Init/classes/class.ilInitialization.php
     *
     * This function is needed to accomplish authentication.
     *
     */
    public static function initSettings()
    {
        global $ilSetting;

        self::initGlobal("ilSetting", "ilSetting",
            "Services/Administration/classes/class.ilSetting.php");

        // check correct setup
        if (!$ilSetting->get("setup_ok"))
        {
            self::abortAndDie("Setup is not completed. Please run setup routine again.");
        }

        // set anonymous user & role id and system role id
        define ("ANONYMOUS_USER_ID", $ilSetting->get("anonymous_user_id"));
        define ("ANONYMOUS_ROLE_ID", $ilSetting->get("anonymous_role_id"));
        define ("SYSTEM_USER_ID", $ilSetting->get("system_user_id"));
        define ("SYSTEM_ROLE_ID", $ilSetting->get("system_role_id"));
        define ("USER_FOLDER_ID", 7);

        // recovery folder
        define ("RECOVERY_FOLDER_ID", $ilSetting->get("recovery_folder_id"));

        // installation id
        define ("IL_INST_ID", $ilSetting->get("inst_id",0));

        // define default suffix replacements
        define ("SUFFIX_REPL_DEFAULT", "php,php3,php4,inc,lang,phtml,htaccess");
        define ("SUFFIX_REPL_ADDITIONAL", $ilSetting->get("suffix_repl_additional"));

        // if(ilContext::usesHTTP())
        // {
        self::buildHTTPPath();
        // }
    }

    /**
     * builds http path
     *
     * Note: Taken from Servcies/Init/classes/class.ilInitialization.php
     *
     * This function is needed to accomplish authentication.
     *
     */
    public static function buildHTTPPath()
    {
        include_once './Services/Http/classes/class.ilHTTPS.php';
        $https = new ilHTTPS();

        if($https->isDetected())
        {
            $protocol = 'https://';
        }
        else
        {
            $protocol = 'http://';
        }
        $host = $_SERVER['HTTP_HOST'];

        $rq_uri = $_SERVER['REQUEST_URI'];

        // security fix: this failed, if the URI contained "?" and following "/"
        // -> we remove everything after "?"
        if (is_int($pos = strpos($rq_uri, "?")))
        {
            $rq_uri = substr($rq_uri, 0, $pos);
        }

        if(!defined('ILIAS_MODULE'))
        {
            $path = pathinfo($rq_uri);
            if(!$path['extension'])
            {
                $uri = $rq_uri;
            }
            else
            {
                $uri = dirname($rq_uri);
            }
        }
        else
        {
            // if in module remove module name from HTTP_PATH
            $path = dirname($rq_uri);

            // dirname cuts the last directory from a directory path e.g content/classes return content

            $module = ilUtil::removeTrailingPathSeparators(ILIAS_MODULE);

            $dirs = explode('/',$module);
            $uri = $path;
            foreach($dirs as $dir)
            {
                $uri = dirname($uri);
            }
        }

        return define('ILIAS_HTTP_PATH',ilUtil::removeTrailingPathSeparators($protocol.$host.$uri));
    }

    /**
     * Provides object properties as stored in table object_data.
     *
     * @param $obj_id
     * @param $aFields array of strings; to query all fields please specify "array('*')"
     * @return mixed
     */
    public static  function getObjectData($obj_id, $aFields)
    {
        global $ilDB;
        $fields = implode(',',$aFields);
        $query = "SELECT ".$fields." FROM object_data WHERE object_data.obj_id=".$obj_id;
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        return $row;
    }
    /**
     * Reads top 1 read event which occured on the object.
     *
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getLatestReadEventTimestamp($obj_id)
    {
        global $ilDB;

        $query = sprintf(' SELECT last_access FROM read_event '.
            'WHERE obj_id = %s '.
            'ORDER BY last_access DESC LIMIT 1',
            $ilDB->quote($obj_id,'integer'));
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);

        return $row['last_access'];
    }

    /**
     * Reads top-k read events which occured on the object.
     * Tries to deliver a list with max -k items
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getTopKReadEventTimestamp($obj_id, $k)
    {
        global $ilDB;

        $query = sprintf(' SELECT last_access FROM read_event '.
            'WHERE obj_id = %s '.
            'ORDER BY last_access DESC LIMIT %s',
            $ilDB->quote($obj_id,'integer'),$k);
        $res = $ilDB->query($query);
        $list = array();
        $cnt = 0;
        while ($row = $ilDB->fetchAssoc($res)){
            $list[] = $row['last_access'];
            $cnt = $cnt + 1;
            if ($cnt == $k) break;
        }

        return $list;
    }

}
