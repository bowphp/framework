<?php
namespace Bow\View;

use Bow\Exception\ViewException;
use Bow\Application\Configuration;

abstract class EngineAbstract
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * Permet de transforme le code du temple en code html
     *
     * @param string $filename
     * @param array $data
     * @return mixed
     */
    public abstract function render($filename, array $data = []);

    /**
     * Permet de verifier le fichier à parser
     *
     * @param string $filename
     * @return string
     * @throws ViewException
     */
    protected function checkParseFile($filename)
    {
        $filename = preg_replace('/@|\./', '/', $filename) . $this->config['view.extension'];

        // Vérification de l'existance du fichier
        if ($this->config['view.path'] !== null) {
            if (! file_exists($this->config['view.path'].'/'.$filename)) {
                throw new ViewException('La vue ['.$filename.'] n\'existe pas. ' . $this->config['view.path'] . '/' . $filename, E_ERROR);
            }
        } else {
            if (! file_exists($filename)) {
                throw new ViewException('La vue ['.$filename.'] n\'existe pas!.', E_ERROR);
            }
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