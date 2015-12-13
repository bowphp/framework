<?php



class Serurity
{
	/**
	 * Stoper les attaques de types xss
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
				$errorList .= "<li><u><strong>" . $key . "</strong></u> not defined</li>";
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
			$this->kill();
		}
	}

	/**
	 * Sanitaze data.
	 * @param string|array|\StdClass
	 * @param bool $secure
	 * @return mixed
	 */
	public static function sanitaze($data, $secure = false)
	{
		if ($secure) {
			$method = "secureString";
		} else {
			$method = "sanitazeString";
		}
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					$data[$key] = $this->$method($value);
				} else if (is_object($value)) {
					$data[$key] = $this->sanitaze($value);
				}
			}
		} else if (is_object($data)) {
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					$data->$key = $this->$method($value);
				} else if (is_array($value)) {
					$data->$key  = $this->sanitaze($value);
				}
			}
		} else if (is_string($data)) {
			$data = $this->$method($data);
		}

		return $data;
	}

	/**
	 * sanitazeString, fonction permettant de nettoyer
	 * une chaine de caractère des caractères ajoutés
	 * par secureString
	 * @param string $data
	 * @return string
	 * @author Franck Dakia <dakiafranck@gmail.com>
	 */
	public static function sanitazeString($data)
	{
		return stripslashes(trim($data));
	}

	/**
	 * secureString, fonction permettant de nettoyer
	 * une chaine de caractère des caractères ',<tag>,&nbsp;
	 * @param string $data
	 * @return string
	 * @author Franck Dakia <dakiafranck@gmail.com>
	 */
	public static function secureString($data)
	{
		return htmlspecialchars(addslashes(trim($data)));
	}

	/**
	 * Createur de token csrf
	 * @param int $time=null
	 * @return void
	 */
	public static function createTokenCsrf($time = null)
	{
		if (!$this->isSessionKey("csrf")) {
			if (is_int($time)) {
				$this->tokenCsrfExpirateTime = $time;
			}
			$this->addSession("csrf", (object) ["token" => $this->generateTokenCsrf(), "expirate" => time() + $this->tokenCsrfExpirateTime]);
		}
	}

	/**
	 * Générer une clé cripté en md5
	 * @return string
	 */
	public static function generateTokenCsrf()
	{
		return md5(base64_encode(openssl_random_pseudo_bytes(23)) . date("Y-m-d H:i:s") . uniqid(rand(), true));
	}

	/**
	 * Retourne un token csrf generer
	 * @return mixed
	 */
	public function getTokenCsrf()
	{
		return $this->session("csrf");
	}

	/**
	 * Vérifie si le token en expire
	 * @param int $time
	 * @return boolean
	 */
	public static function tokenCsrfTimeIsExpirate($time)
	{
		if ($this->isSessionKey("csrf")) {
			if ($this->getTokenCsrf()->expirate >= (int) $time) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Vérifie si token csrf est valide
	 * @param string $token
	 * @param int $time[optional]
	 * @return boolean
	 */
	public static function verifyTokenCsrf($token, $time = null)
	{
		$status = false;
		if ($this->isSessionKey("csrf") && $token === $this->getTokenCsrf()->token) {
			$status = true;
			if ($time !== null && is_int($time)) {
				$status = $status && $this->tokenCsrfTimeIsExpirate($time);
			}
		}
		return $status;
	}

	/**
	 * Détruie le token
	 */
	public static function killTokenCsrf()
	{
		$this->removeSession("csrf");
	}

}
