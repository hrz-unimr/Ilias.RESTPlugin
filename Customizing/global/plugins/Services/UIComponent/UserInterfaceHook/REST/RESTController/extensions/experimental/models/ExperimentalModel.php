<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\experimental;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('./Services/Database/classes/class.ilAuthContainerMDB2.php');


class ExperimentalModel
{
    public static function initSettings()
    {
        global $ilSetting;

        Libs\RESTLib::initGlobal('ilSetting', 'ilSetting',
            'Services/Administration/classes/class.ilSetting.php');

        // check correct setup
        if (!$ilSetting->get('setup_ok'))
            self::abortAndDie('Setup is not completed. Please run setup routine again.');

        // set anonymous user & role id and system role id
        define ('ANONYMOUS_USER_ID', $ilSetting->get('anonymous_user_id'));
        define ('ANONYMOUS_ROLE_ID', $ilSetting->get('anonymous_role_id'));
        define ('SYSTEM_USER_ID', $ilSetting->get('system_user_id'));
        define ('SYSTEM_ROLE_ID', $ilSetting->get('system_role_id'));
        define ('USER_FOLDER_ID', 7);

        // recovery folder
        define ('RECOVERY_FOLDER_ID', $ilSetting->get('recovery_folder_id'));

        // installation id
        define ('IL_INST_ID', $ilSetting->get('inst_id',0));

        // define default suffix replacements
        define ('SUFFIX_REPL_DEFAULT', 'php,php3,php4,inc,lang,phtml,htaccess');
        define ('SUFFIX_REPL_ADDITIONAL', $ilSetting->get('suffix_repl_additional'));

        self::buildHTTPPath();
    }

    /**
     * builds http path
     */
    public static function buildHTTPPath()
    {
        include_once('./Services/Http/classes/class.ilHTTPS.php');
        $https = new \ilHTTPS();

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

        // security fix: this failed, if the URI contained '?' and following '/'
        // ->we remove everything after '?'
        if (is_int($pos = strpos($rq_uri, '?')))
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

            $module = \ilUtil::removeTrailingPathSeparators(ILIAS_MODULE);

            $dirs = explode('/',$module);
            $uri = $path;
            foreach($dirs as $dir)
            {
                $uri = dirname($uri);
            }
        }

        return define('ILIAS_HTTP_PATH',\ilUtil::removeTrailingPathSeparators($protocol.$host.$uri));
    }




}
