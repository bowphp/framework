<?php
namespace Bow\View;

use Bow\Config\Config;
use Bow\View\Exception\ViewException;

abstract class EngineAbstract
{
    /**
     * Liste des helpers
     */
    const HELPERS = [
        'secure' => 'secure',
        'route' => 'route',
        'bow_hash' => 'bow_hash',
        'config' => 'config',
        'faker' => 'faker',
        'env' => 'env',
        'app_mode' => 'app_mode',
        'app_lang' => 'app_lang',
        'flash' => 'flash',
        'cache' => 'cache',
        'encrypt' => 'encrypt',
        'decrypt' => 'decrypt',
        'collect' => 'collect',
        'url' => 'url',
        'get_header' => 'get_header',
        'input' => 'input',
        'response' => 'response',
        'request' => 'request',
        'sanitaze' => 'sanitaze',
        'slugify' => 'slugify',
        'session' => 'session',
        'form' => 'form',
        'csrf_token' => 'csrf_token',
        'csrf_field' => 'csrf_field',
        'trans' => 'trans',
        'escape' => 'e',
        'old' => 'old'
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Permet de transforme le code du temple en code html
     *
     * @param  string $filename
     * @param  array  $data
     * @return mixed
     */
    abstract public function render($filename, array $data = []);

    /**
     * Permet de verifier le fichier à parser
     *
     * @param  string $filename
     * @param  bool   $extended
     * @return string
     * @throws ViewException
     */
    protected function checkParseFile($filename, $extended = true)
    {
        $tmp_filename = preg_replace('/@|\./', '/', $filename) . $this->config['view.extension'];

        // Vérification de l'existance du fichier
        if ($this->config['view.path'] !== null) {
            if (!file_exists($this->config['view.path'].'/'.$tmp_filename)) {
                throw new ViewException('La vue ['.$tmp_filename.'] n\'existe pas. ' . $this->config['view.path'] . '/' . $filename, E_ERROR);
            }
        } else {
            if (!file_exists($tmp_filename)) {
                throw new ViewException('La vue ['.$tmp_filename.'] n\'existe pas!.', E_ERROR);
            }
        }

        if ($extended) {
            $filename = $tmp_filename;
        }

        return $filename;
    }

    /**
     * Permet de retourne le nom de template charge
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
