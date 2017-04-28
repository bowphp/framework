<?php
namespace Bow\Security;

use Bow\Session\Session;

/**
 * Class Security
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Security
{
    /**
     * @static int
     */
    private static $tokenCsrfExpirateTime;

    /**
     * @var string
     */
    private static $key = "";

    /**
     * @var null
     */
    private static $iv = null;

    /**
     * setKey modifie la clé de cryptage
     *
     * @param string $key
     */
    public static function setkey($key)
    {
        static::$key = $key;
    }

    /**
     * Les attaques de types xss
     *
     * @param array $verifyData
     * @param array $enableData
     *
     * @return bool
     */
    public static function verifiySideBySide($verifyData, $enableData)
    {
        $error = false;

        foreach ($verifyData as $key => $value) {
            if (!in_array($key, $enableData)) {
                $error = true;
            }
        }

        return $error;
    }

    /**
     * Sécurise les données
     *
     * @param mixed $data
     * @param bool $secure
     *
     * @return mixed
     */
    public static function sanitaze($data, $secure = false)
    {
        // récupération de la fonction à la lance.
        $method = $secure === true ? 'secureData' : 'sanitazeData';
        // strict integer regex
        $rNum = '/^[0-9]+(\.[0-9]+)?$/';

        if (is_string($data)) {
            if (preg_match($rNum, $data)) {
                $data = (int) $data;
            } else {
                $data = self::$method($data);
            }
            return $data;
        }

        if (is_numeric($data)) {
            return $data;
        }

        if (is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = self::sanitaze($value, $secure);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach($data as $key => $value) {
                $data->$key = self::sanitaze($value, $secure);
            }
            return $data;
        }

        return $data;
    }

    /**
     * SanitazeString, fonction permettant de nettoyer
     * une chaine de caractère des caractères ajoutés
     * par secureString
     *
     * @param string $data les données a néttoyé
     *
     * @return string
     *
     * @author Franck Dakia <dakiafranck@gmail.com>
     */
    public static function sanitazeData($data)
    {
        return stripslashes(stripslashes(trim($data)));
    }

    /**
     * secureString, fonction permettant de nettoyer
     * une chaine de caractère des caractères ',<tag>,&nbsp;
     *
     * @param string $data les données a sécurisé
     *
     * @return string
     *
     * @author Franck Dakia <dakiafranck@gmail.com>
     */
    public static function secureData($data)
    {
        return htmlspecialchars(addslashes(trim($data)));
    }

    /**
     * Createur de token csrf
     *
     * @param int $time=null
     *
     * @return bool
     */
    public static function createCsrfToken($time = null)
    {
        if (Session::has('__bow.csrf')) {
            return false;
        }

        if (is_int($time)) {
            static::$tokenCsrfExpirateTime = $time;
        }

        $token = static::generateCsrfToken();

        Session::add('__bow.csrf', (object) [
            'token' => $token,
            'expirate' => time() + static::$tokenCsrfExpirateTime,
            'field' => '<input type="hidden" name="_token" value="' . $token .'"/>'
        ]);

        Session::add('_token', $token);

        return true;
    }

    /**
     * Générer une clé crypté en md5
     *
     * @return string
     */
    public static function generateCsrfToken()
    {
        return base64_encode(base64_encode(openssl_random_pseudo_bytes(23)) . date('Y-m-d H:i:s') . uniqid(rand(), true));
    }

    /**
     * Retourne un token csrf générer
     *
     * @return mixed
     */
    public static function getCsrfToken()
    {
        return Session::get('__bow.csrf');
    }

    /**
     * Vérifie si le token en expire
     *
     * @param int $time le temps d'expiration
     *
     * @return boolean
     */
    public static function tokenCsrfTimeIsExpirate($time = null)
    {
        if (Session::has('__bow.csrf')) {
            if ($time === null) {
                $time = time();
            }

            if (Session::has('__bow.csrf')->expirate >= (int) $time) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si token csrf est valide
     *
     * @param string $token le token a vérifié
     * @param bool $strict le niveau de vérification
     *
     * @return boolean
     */
    public static function verifyCsrfToken($token, $strict = false)
    {
        $status = false;

        if (Session::has('__bow.csrf')) {
            if ($token === Session::has('__bow.csrf')->token) {
                $status = true;
                if ($strict) {
                    $status = $status && static::tokenCsrfTimeIsExpirate(time());
                }
            }
        }

        return $status;
    }

    /**
     * Détruie le token
     */
    public static function clearCsrfToken()
    {
        Session::remove('__bow.csrf');
        Session::remove('_token');
    }

    /**
     * crypt
     *
     * @param string $data les données a encrypté
     * @return string
     */
    public static function encrypt($data)
    {
        $iv_size = @mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        static::$iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_data = @mcrypt_encrypt(MCRYPT_BLOWFISH, static::$key, $data, MCRYPT_MODE_CBC, static::$iv);

        return base64_encode($encrypted_data . static::$iv);
    }

    /**
     * decrypt
     *
     * @param string $encrypted_data les données a décrypté
     *
     * @return string
     */
    public static function decrypt($encrypted_data)
    {
        $iv_size = @mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $encrypted_data = base64_decode($encrypted_data);
        $start = strlen($encrypted_data) - $iv_size;
        $iv = substr($encrypted_data, $start, $iv_size);
        $encrypted_data = substr($encrypted_data, 0, $start);
        $decrypted_data = @mcrypt_decrypt(MCRYPT_BLOWFISH, static::$key, $encrypted_data, MCRYPT_MODE_CBC, $iv);

        return static::sanitaze(trim($decrypted_data));
    }
}