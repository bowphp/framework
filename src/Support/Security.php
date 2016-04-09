<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Core\AppConfiguration;

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
		AppConfiguration::takeInstance()->setAppkey($key);
	}

	/**
	 * Stopeur les attaques de types xss
	 *
	 * @param array $verifyData
	 * @param array $enableData
	 */
	public static function attaqueStoper($verifyData, $enableData)
	{
		$errorList = '';
		$error = false;
		
		foreach ($verifyData as $key => $value) {
			if (!in_array($key, $enableData)) {
				$error = true;
				$errorList .= "<li><b><strong>" . $key . "</strong></b> not defined</li>";
			}
		}

		/**
		 * Vérification d'erreur
		 */
		if ($error) {
			echo '<div style="border-radius: 3px; border: 1px solid #eee; background: tomato; padding: 10px; ">';
			echo "<h1>Attaque stoped</h1>";
			echo "<ul style=\"color: white\">";
			echo $errorList;
			echo "</ul>";
			echo "</div>";
			// On arrête tout.
			die();
		}
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
		$method = $secure === true ? "secureString" : "sanitazeString";
		// strict integer regex 
		$rNum = "/^\d+$/";

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					if (preg_match($rNum, $value)) {
						$data[$key] = (int) $value;
						continue;
					}
					$data[$key] = static::$method($value);
				} else if (is_object($value)) {
					$data[$key] = static::sanitaze($value);
				}
			}
		} else if (is_object($data)) {
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					if (preg_match($rNum, $value)) {
						$data->$key = (int) $value;
						continue;
					}
					$data->$key = static::$method($value);
				} else if (is_array($value)) {
					$data->$key  = static::sanitaze($value);
				}
			}
		} else if (is_string($data)) {
			if (preg_match($rNum, $data)) {
				$data = (int) $data;
			} else {
				$data = static::$method($data);
			}
		}

		return $data;
	}

	/**
	 * sanitazeString, fonction permettant de nettoyer
	 * une chaine de caractère des caractères ajoutés
	 * par secureString
	 * 
	 * @param string $data
	 * @return string
	 * @author Franck Dakia <dakiafranck@gmail.com>
	 */
	private static function sanitazeString($data)
	{
		return stripslashes(trim($data));
	}

	/**
	 * secureString, fonction permettant de nettoyer
	 * une chaine de caractère des caractères ',<tag>,&nbsp;
	 * 
	 * @param string $data
	 * @return string
	 * @author Franck Dakia <dakiafranck@gmail.com>
	 */
	private static function secureString($data)
	{
		return htmlspecialchars(addslashes(trim($data)));
	}

	/**
	 * Createur de token csrf
	 * @param int $time=null
	 * @return bool
	 */
	public static function createTokenCsrf($time = null)
	{
		if (!Session::has("bow.csrf")) {
			if (is_int($time)) {
				static::$tokenCsrfExpirateTime = $time;
			}

			$token = static::generateTokenCsrf();

			Session::add("bow.csrf", (object) [
				"token" => $token,
				"expirate" => time() + static::$tokenCsrfExpirateTime,
				"field" => '<input type="hidden" name="csrf_token" value="' . $token .'"/>'
			]);

            return true;
		}

        return false;
	}

	/**
	 * Générer une clé crypté en md5
	 * @return string
	 */
	public static function generateTokenCsrf()
	{
		return md5(base64_encode(openssl_random_pseudo_bytes(23)) . date("Y-m-d H:i:s") . uniqid(rand(), true));
	}

	/**
	 * Retourne un token csrf générer
	 * @return mixed
	 */
	public static function getTokenCsrf()
	{
		return Session::get("bow.csrf");
	}

	/**
	 * Vérifie si le token en expire
	 * @param int $time
	 * @return boolean
	 */
	public static function tokenCsrfTimeIsExpirate($time = null)
	{
		if (Session::has("bow.csrf")) {
			if ($time === null) {
				$time = time();
			}

			if (static::getTokenCsrf()->expirate >= (int) $time) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Vérifie si token csrf est valide
	 * @param string $token
	 * @param bool $strict
	 * @return boolean
	 */
	public static function verifyTokenCsrf($token, $strict = false)
	{
		$status = false;
		
		if (Session::has("bow.csrf")) {
			if ($token === static::getTokenCsrf()->token) {
				$status = true;
				if ($strict !== true) {
					$status = $status && static::tokenCsrfTimeIsExpirate(time());
				}
			}
		}

		return $status;
	}

	/**
	 * Détruie le token
	 */
	public static function killTokenCsrf()
	{
		Session::remove("bow.csrf");
	}

	/**
	 * crypt
	 * 
	 * @param string $data
	 * @return string
	 */
	public static function encrypt($data)
	{
        static::$key = AppConfiguration::takeInstance()->getAppkey();
		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
		static::$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$encrypted_data = mcrypt_encrypt(MCRYPT_BLOWFISH, static::$key, $data, MCRYPT_MODE_CBC, static::$iv);

	 	return base64_encode($encrypted_data . static::$iv);
	}

	/**
	 * decrypt
	 * 
	 * @param string $encrypted_data
	 * @return string
	 */
	public static function decrypt($encrypted_data)
	{
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
		$encrypted_data = base64_decode($encrypted_data);
        $start = strlen($encrypted_data) - $iv_size;
        $iv = substr($encrypted_data, $start, $iv_size);
        $encrypted_data = substr($encrypted_data, 0, $start);
		$decrypted_data = mcrypt_decrypt(MCRYPT_BLOWFISH, static::$key, $encrypted_data, MCRYPT_MODE_CBC, $iv);

		return $decrypted_data;
	}
}