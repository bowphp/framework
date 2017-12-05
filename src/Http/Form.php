<?php
namespace Bow\Http;

/**
 * Class Form
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Form
{
    private static $form = "";

    /**
     * Les informations prédéfinis.
     *
     * @var array
     */
    private static $with = [];

    /**
     * @var Form
     */
    private static $instance;

    /**
     * @return Form
     */
    public static function singleton()
    {
        if (!static::$instance instanceof Form) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Permet de récupérer une valeur dans la tableau $with
     *
     * @param  $key
     * @param  $default
     * @return string
     */
    private static function getAssociateValue($key, $default = '')
    {
        return isset(static::$with[$key]) ? static::$with[$key] : $default;
    }

    /**
     * Permet de récupérer une valeur dans la tableau $with
     *
     * @param  array $attributes
     * @return string
     */
    private static function formatAttributes($attributes)
    {
        $attr = '';

        foreach ($attributes as $key => $attribute) {
            $attr .= $key .'="'.$attribute.'" ';
        }

        return trim($attr);
    }

    /**
     * Ajout le tag <input type="text">
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return void
     */
    public static function text($name, $value = "", $attributes = [])
    {
        $value = static::getAssociateValue($name, $value);
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<input type=\"text\" value=\"{$value}\" name=\"{$name}\" ".$attributes."/>";
    }

    /**
     * Ajout le tag <input type="password">
     *
     * @param string $name
     * @param string $value=""
     * @param array  $attributes
     *
     * @return void
     */
    public static function password($name, $value = "", $attributes = [])
    {
        $value = static::getAssociateValue($name, $value);
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<input type=\"password\" name=\"{$name}\" value=\"{$value}\" ".$attributes."/>";
    }


    /**
     * Ajout le tag <input type="hidden">
     *
     * @param string $name
     * @param string $value=""
     * @param array  $attributes
     *
     * @return void
     */
    public static function hidden($name, $value = "", $attributes = [])
    {
        $value = static::getAssociateValue($name, $value);
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<input type=\"hidden\" value=\"{$value}\" name=\"{$name}\" ".$attributes."/>";
    }

    /**
     * Ajout le tag <input type="file">
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return void
     */
    public static function file($name, array $attributes = [])
    {
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<input type=\"file\" name=\"{$name}\" ".$attributes."/>";
    }

    /**
     * Ajout le tag <input type="submit">
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return void
     */
    public static function submit($value, array $attributes = [])
    {
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<button type=\"submit\" ".$attributes.">{$value}</button>";
    }

    /**
     * Ajout le tag <input type="submit">
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return void
     */
    public static function button($value, array $attributes = [])
    {
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<button ".$attributes.">{$value}</button>";
    }

    /**
     * Ajout le tag <textarea></textarea>
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return void
     */
    public static function textarea($name, $value = "", array $attributes = [])
    {
        $value = static::getAssociateValue($name, $value);
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<textarea name=\"{$name}\" ".$attributes.">" . $value . "</textarea>";
    }

    /**
     * Ajout le tag <input type="checkbox">
     *
     * @param string $name
     * @param bool   $checked=false
     * @param string $value=""
     *
     * @return void
     */
    public static function checkbox($name, $checked = false, $value = "")
    {
        $value = static::getAssociateValue($name, $value);
        self::$form .= "<input type=\"checkbox\" name=\"{$name}\" value=\"{$value}\" ". ($checked == true ? 'cheched' : '')."/>";
    }

    /**
     * Ajout le tag radio.
     *
     * @param string $name
     * @param bool   $checked=false
     * @param string $value
     *
     * @return void
     */
    public static function radio($name, $checked = false, $value = "")
    {
        $value = static::getAssociateValue($name, $value);
        self::$form .= "<input type=\"radio\" name=\"{$name}\" value=\"{$value}\" " . ($checked == true ? 'cheched' : '')."/>";
    }

    /**
     * Ajout le tag radio.
     *
     * @param string      $name
     * @param array       $options
     * @param string|null $selected
     * @param array       $attributes
     *
     * @return void
     */
    public static function select($name, array $options = [], $selected = null, array $attributes = [])
    {
        $attributes = static::formatAttributes($attributes);
        self::$form .= "<select name=\"$name\" ".$attributes.">";
        $oldValue = static::getAssociateValue($name);

        foreach ($options as $key => $value) {
            if ($oldValue !== "") {
                $key = $oldValue;
            }

            self::$form .= "<option value=\"{$key}\" " . ($selected == $key ? "selected" : "") . ">" . $value . "</option>";
        }

        self::$form .= "</select>";
    }

    /**
     * Ajout le tag <fieldset>
     *
     * @param null|string $legend
     *
     * @return void
     */
    public static function addFieldSet($legend = null)
    {
        self::$form .= "<fieldset>";

        if (is_string($legend)) {
            self::$form .= "<legend>{$legend}</legend>";
        }
    }

    /**
     * Ferméture du tag fieldset
     *
     * @return void
     */
    public static function closeFieldSet()
    {
        self::$form .= "</fieldset>";
    }

    /**
     * Ajout un retoure chariot de la colléction de balise
     *
     * @return void
     */
    public static function outline()
    {
        self::$form .= "<br/>";
    }

    /**
     * Ajout le tag <label></label>
     *
     * @param string      $title
     * @param string|null $for=null
     *
     * @return void
     */
    public static function label($title, $for = null)
    {
        self::$form .= "<label ". ($for !== null ? "for={$for}": "") .">" . $title . "</label>";
    }

    /**
     * Creation définitive du formulaire.
     *
     * @param $method
     * @param $action
     * @param string     $id
     * @param bool|false $enctype
     *
     * @return string
     */
    public static function compile($method, $action, $enctype = false, $id = "form")
    {
        return "<form id=\"$id\" method=\"{$method}\" action=\"{$action}\" ".($enctype === true ? 'enctype="multipart/form-data"': "").">". self::$form . "</form>";
    }

    /**
     * Associé un model
     *
     * @param  array $data
     * @return void
     */
    public static function with($data)
    {
        static::$with = $data;
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        return null;
    }
}
