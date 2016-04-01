<?php

namespace Bow\Core;

use Bow\Support\Util;
use Bow\Exception\ApplicationException;

class AppConfiguration
{
    /**
     * @var string
     */
    private $debug = "develope";

    /**
     * @var AppConfiguration
     */
    private static $instance;
    /**
     * Définie le systeme de template
     * @var string|null
     */
    private $engine = null;
    /**
     * Répresente le chemin vers la vue.
     * @var null|string
     */
    private $views = null;
    /**
     * @var string
     */
    private $appname = "Bow Application";
    /**
     * @var string
     */
    private $logDirecotoryName;
    /**
     * @var string|false
     */
    private $cache = false;
    /**
     * @var array
     */
    private $names = [];
    /**
     * @var string
     */
    private $timezone;
    /**
     * @var int
     */
    private $tokenExpirateTime = 3600;
    /**
     * @var string
     */
    private $approot;
    /**
     * @var string
     */
    private $template_extension = ".php";
    /**
     * @var string
     */
    private $app_key = "Eda4W+AyMDE2LTAyLTE2IDIwOjM2OjE0";
    /**
     * @var bool
     */
    private $autoReload = true;

    /**
     * @param $config
     * @throws \Bow\Exception\UtilException
     */
    private final function __construct($config)
    {
        /**
         * Chargement complet de toute la configuration de Bow
         */
        $this->appname            = $config->app_name;
        $this->logDirecotoryName  = $config->log_direcotory_name;
        $this->views              = $config->views_path;
        $this->engine             = $config->template_engine;
        $this->cache              = $config->template_cache_folder;
        $this->names              = $config->classes;
        $this->timezone           = $config->timezone;
        $this->debug              = $config->debug;
        $this->tokenExpirateTime  = $config->token_expirate_time;
        $this->template_extension = $config->template_extension;
        $this->autoReload         = $config->template_auto_reload_cache_views;

        if (isset($config->app_root)) {
            $this->approot = $config->app_root;
        }

        if (is_file($config->app_key)) {
            $this->app_key = file_get_contents($config->app_key);
        }

        if (isset($config->timezone)) {
            Util::setTimezone($config->timezone);
        }
    }

    /**
     * Ferméture de la fonction magic __clone pour optimizer le singleton
     */
    private final function __clone(){}

    /**
     * takeInstance singleton
     * @param array $config
     * @return AppConfiguration
     */
    public static function configure($config) {
        if (! static::$instance instanceof AppConfiguration) {
            static::$instance = new self($config);
        }

        return static::$instance;
    }

    /**
     * takeInstance singleton
     * @return AppConfiguration
     */
    public static function takeInstance() {
        return static::$instance;
    }

    /**
     * Retourne Application key
     *
     * @return string
     */
    public function getAppkey()
    {
        return $this->app_key;
    }
    /**
     * configure, Application key
     * @param string $key
     * @throws ApplicationException
     * @return string
     */
    public function setAppkey($key)
    {
        $old = $this->app_key;

        if (!is_string($key)) {
            throw new ApplicationException("Le parametre doit etre une chaine de carrectere.", E_USER_ERROR);
        }

        $this->app_key = $key;
        return $old;
    }

    /**
     * setAppName
     * 
     * @param string $newAppName
     * @throws ApplicationException
     * @return string
     */
    public function setAppname($newAppName)
    {
        $old = $newAppName;

        if (!is_string($newAppName)) {
            throw new ApplicationException("Le parametre doit etre une chaine de carrectere.", E_USER_ERROR);
        }

        $this->appname = $newAppName;

        return $old;
    }

    /**
     * getAppName
     *
     * @return string
     */
    public function getAppname()
    {
        return $this->appname;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     *
     * @param string $viewPath
     * @throws ApplicationException
     * @return string
     */
    public function setViewpath($viewPath)
    {
        $old = $this->views;

        if (!realpath($viewPath)) {
            throw new ApplicationException("Ce chemin n'est pas valide.", E_USER_ERROR);
        }

        $this->views = $viewPath;

        return $old;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function getViewpath()
    {
        return $this->views;
    }

    /**
     * setCachePath
     * 
     * @param string $newCachePath
     * @throws ApplicationException
     * @return string
     */
    public function setCachepath($newCachePath)
    {
        $old = $this->cache;

        if (!realpath($newCachePath)) {
            throw new ApplicationException("Ce chemin n'est valide.", E_USER_ERROR);
        }

        $this->cache = $newCachePath;

        return $old;
    }

    /**
     * getCachePath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function getCachepath()
    {
        return $this->cache;
    }
    
    /**
     * setLogPath configuration du path du répertoir de log
     *
     * @param string $newLogPath
     * @throws ApplicationException
     * @return string
     */
    public function setLogpath($newLogPath)
    {
        $old = $this->logDirecotoryName;
        
        if (!realpath($newLogPath)) {
            throw new ApplicationException("Ce chemin n'est valide.", E_USER_ERROR);
        }

        $this->logDirecotoryName = $newLogPath;

        return $old;
    }

    /**
     * getLogPath retourne la configuration du path du répertoir de log
     * 
     * @return string
     */
    public function getLogpath()
    {
        return $this->logDirecotoryName;
    }

    /**
     * @param string $log
     * @throws ApplicationException
     * @return string
     */
    public function setLogLevel($log)
    {
        $old = $this->debug;

        if (!in_array($log, ["development", "production"])) {
            throw new ApplicationException("$log n'est pas accepte. <i>development|production</i>", E_USER_ERROR);
        }

        $this->debug = $log;

        return $old;
    }

    public function getLogLevel()
    {
        return $this->debug;
    }

    /**
     * getTimezone retourne la configuration de la TL
     * 
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * getNamespace retourne la configuration des namespaces
     *
     * @return array
     */
    public function getNamespace()
    {
        return $this->names;
    }

    /**
     * getEngine retourne le nom du moteur de template définir
     * 
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * setApproot
     *
     * @param string $newApproot
     * @return string
     */
    public function setApproot($newApproot)
    {
        $old = $this->approot;

        if (is_string($this->approot)) {
            $this->approot = $newApproot;
        }

        return $old;
    }

    /**
     * Retourn la route principal de l'application.
     * @return string
     */
    public function getApproot()
    {
        return $this->approot;
    }

    /**
     * modifier l'extension de template.
     * @param string $extension
     * @return string
     */
    public function setTemplateExtension($extension)
    {
        $old = $this->template_extension;

        if (is_string($extension)) {
            $this->template_extension = $extension;
        }

        return $old;
    }

    /**
     * retourne l'extension de template.
     * @return string
     */
    public function getTemplateExtension()
    {
        return $this->template_extension;
    }

    public function getCacheAutoReload()
    {
        return $this->autoReload;
    }
}