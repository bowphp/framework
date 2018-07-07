<?php

namespace Bow\Resource;

interface FilesystemInterface
{

    /**
     * UploadFile, fonction permettant de uploader un fichier
     *
     * @param  array    $file      information sur le fichier, $_FILES
     * @param  string   $location
     * @param  int      $size
     * @param  array    $extension
     * @param  callable $cb
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function store($file, $location, $size, array $extension, callable $cb);

    /**
     * Ecrire à la suite d'un fichier spécifier
     *
     * @param  string $file    nom du fichier
     * @param  string $content content a ajouter
     * @return bool
     */
    public function append($file, $content);

    /**
     * Ecrire au début d'un fichier spécifier
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend($file, $content);

    /**
     * Put
     *
     * @param  $file
     * @param  $content
     * @return bool
     */
    public function put($file, $content);

    /**
     * Supprimer un fichier
     *
     * @param  string $file
     * @return boolean
     */
    public function delete($file);

    /**
     * Alias sur readInDir
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname);

    /**
     * Lire le contenu du dossier
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname);

    /**
     * Crée un répertoire
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false);

    /**
     * Récuper le contenu du fichier
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename);

    /**
     * Copie le contenu d'un fichier source vers un fichier cible.
     *
     * @param  string $targerFile
     * @param  string $sourceFile
     * @return bool
     */
    public function copy($targerFile, $sourceFile);

    /**
     * Rénomme ou déplace un fichier source vers un fichier cible.
     *
     * @param $targer_file
     * @param $source_file
     */
    public function move($targer_file, $source_file);

    /**
     * Vérifie l'existance d'un fichier
     *
     * @param  $filename
     * @return bool
     */
    public function exists($filename);

    /**
     * L'extension du fichier
     *
     * @param  $filename
     * @return string
     */
    public function extension($filename);

    /**
     * isFile aliase sur is_file.
     *
     * @param  $filename
     * @return bool
     */
    public function isFile($filename);

    /**
     * isDirectory aliase sur is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public function isDirectory($dirname);

    /**
     * Permet de résolver un path.
     * Donner le chemin absolute d'un path
     *
     * @param  $filename
     * @return string
     */
    public function resolvePath($filename);
}
