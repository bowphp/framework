<?php

namespace Bow\Core;

class AppConfiguration
{
    /**
     * Patter Singleton
     * 
     * @var string
     */
    private $loglevel = "dev";

    /**
     * @var AppConfiguration
     */
    private static $instance;

    /**
     * Définie le systeme de template
     *
     * @var string|null
     */
    private $engine = null;

    /**
     * Répresente le chemin vers la vue.
     * 
     * @var null|string
     */
    private $views = null;

    /**
     * Répertoire de log d'erreur
     *
     * @var string
     */ 
    private $logDirecotoryName = "";

    private function __construct($config)
    {
        $this->appname = $config->appname;
        $this->logDirecotoryName = $config->logDirecotoryName;
        $this->views = $config->views;
        $this->engine = $config->template;
        $this->cache = $config->cacheFolder;
        $this->names = $config->names;
        $this->timezone = $config->timezone;
        $this->loglevel = $config->loglevel;
        $this->tokenExpirateTime = $config->tokenExpirateTime;
        $this->renderEngine = $config->template;
        $this->cacheFolder = $config->cacheFolder;
    }

    private function __clone(){}

    /**
     * takeInstance singleton
     * @param array $config
     * @return self
     */
    public static function configure($config) {
        if (! static::$instance instanceof AppConfiguration) {
            static::$instance = new self($config);
        }

        return static::$instance;
    }
    /**
     * takeInstance singleton
     * @param array $config
     * @return self
     */
    public static function takeInstance() {
        return static::$instance;
    }

    /**
     * setAppName
     * 
     * @param string $newAppName
     * @return string
     */
    public function setAppname($newAppName)
    {
        $old = $newAppName;

        if (is_string($newAppName)) {
            $this->appname = $newAppName;
        }

        return $old;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function setViewpath($viewPath)
    {
        $old = $this->views;

        if (realpath($viewPath)) {
            $this->views = $viewPath;
        }

        return $old;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function getAppnamespace()
    {
        return $this->names;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function setPublicpath($newPublicPath)
    {
        $old = $this->public;

        if ($newPublicPath) {
            $this->public = $newPublicPath;
        }

        return $old;
    }

    /**
     * getViewPath retourne configuration du path du repertoire du cache
     * 
     * @return string
     */
    public function getPublicpath($newPublicPath)
    {
        return  $this->public;
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
     * @return string
     */
    public function setCachepath($newCachePath)
    {
        $old = $newCachePath;

        if (realpath($newCachePath)) {
            $this->cache = $newCachePath;
        }

        return $newCachePath;
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
     * setLogPath configuration du path du repertoir de log
     * 
     * @return string
     */
    public function setLogpath($newLogPath)
    {
        $old = $this->logDirecotoryName;
        
        if (realpath($newLogPath)) {
            $this->logDirecotoryName = $newLogPath;
        }

        return $old;
    }

    /**
     * getLogPath retourne la configuration du path du repertoir de log
     * 
     * @return string
     */
    public function getLogpath()
    {
        return $this->logDirecotoryName;
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
     * getNamespace retourne la configuration des namespaces
     * 
     * @return array
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * getNamespace retourne la configuration des namespaces
     * 
     * @return array
     */
    public function getRootpath()
    {
        return $this->root;
    }

    /**
     * getNamespace retourne la configuration des namespaces
     * 
     * @return array
     */
    public function setRootpath($newRoot)
    {
        $old = $this->root;
        $this->root = $newRoot;
        return $old;
    }
}