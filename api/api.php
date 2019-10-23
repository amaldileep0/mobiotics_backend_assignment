<?php

header("Content-type: application/json");
header("Access-Control-Allow-Origin: *");

require_once("Rest.inc.php");
require_once("StringHelper.php");

class API extends REST 
{
	public $data = "";
	const DB_SERVER 	= "localhost";
	const DB_USER 		= "root";
	const DB_PASSWORD 	= "pass";
	const DB 			= "test_mb";		

	private $_db = NULL;

	public function __construct()
	{
		parent::__construct();	// Init parent contructor
		$this->dbConnection();	// Initiate Database connection
	}
		
	private function dbConnection()
	{
		$this->_db = mysqli_connect(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
		if (mysqli_connect_errno()) {
			die("Failed to connect to MySQL: " . mysqli_connect_error());
		}			
	}

	public function run()
	{ 	

		$action = trim($_REQUEST['type']);
		if (!empty($action)) { 	 
			switch (strtolower($action)) {
				case 'login':
					$this->login();
					break;
				case 'signup':
					$this->signup();
					break;
				case 'forgotpassword':
					$this->forgotPassword();
					break;
				case 'resetpassword':
					$this->resetPassword();
					break;
				case 'addrequest':
					$this->addRequest();
					break;
				case 'updaterequest':
					$this->updateRequest();
					break;
				case 'listrequest':
					$this->listRequest();
					break;
				case 'deleterequest':
					$this->deleteRequest();
					break;
				default:
					$this->response('', 404);
					break;
			}
		}
	}
	
	public function multid_sort($arr, $index)
	{
		$b = [];
		$c = [];
		foreach ($arr as $key => $value) {
			$b[$key] = $value[$index];
		}
		arsort($b);
		foreach ($b as $key => $value) {
			$c[] = $arr[$key];
		}
		return $c;
	}
	
	
	private function signup()
	{
		if ($this->get_request_method() != "POST") {
			$this->response('', 406);
		}				  
		
		$name = isset($this->_request['name']) ? $this->_request['name'] : "";
		$email = isset($this->_request['email']) ? strtolower($this->_request['email']) : "";
		$password = isset($this->_request['password']) ? $this->_request['password'] : "";
		$confirmPassword = isset($this->_request['confirmPassword']) ? $this->_request['confirmPassword'] : "";

		if(!$name) {
			$error = ['success' => false, "message" => "Name must not be blank."];
			$this->response($this->json($error), 400);
		}
	
		if(!$email) {
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
 
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}
		
		if(!$password) {
			$error = ['success' => false, "message" => "Password must not be blank."];
			$this->response($this->json($error), 400);
		}
		
		if(!$confirmPassword) {
			$error = ['success' => false, "message" => "Confirm Password must not be blank"];
			$this->response($this->json($error), 400);
		}

		if (strcmp($password, $confirmPassword) !== 0) { 
			$error = ['success' => false, "message" => "Confirm Password must match password."];
			$this->response($this->json($error), 400); 
		}
		$query = $this->_db->prepare("SELECT * FROM users WHERE email = ?");
		$query->bind_param("s", $email);
		$query->execute();
		$res = $query->get_result();
	
		if($res->num_rows > 0) {
			$error = ['success' => false, "message" => "The given Email already exists"];
			$this->response($this->json($error), 400);
		} else {
			$password = $this->generatePasswordHash($password);
			$stmt = $this->_db->prepare("INSERT INTO users (name, email, password, createdAt) VALUES (?, ?, ?, ?)");
			$stmt->bind_param("sssi", $name, $email, $password, time()); 
			$stmt->execute();
			$result["message"] = "Registration successfull. Please login to continue.";	
			$this->response($this->json($result), 200);
		}
		$this->response($this->json(["success" => false, "message" => "Internal server error"]), 500);
	}

	protected function generatePasswordHash($password, $cost = null)
    {   
        $cost = 12;
        if (function_exists('password_hash')) {
            /* @noinspection PhpUndefinedConstantInspection */
            return password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
        }

        $salt = $this->generateSalt($cost);
        $hash = crypt($password, $salt);
        // strlen() is safe since crypt() returns only ascii
        if (!is_string($hash) || strlen($hash) !== 60) {
            throw new Exception('Unknown error occurred while generating hash.');
        }
        return $hash;
    }

    public function validatePassword($password, $hash)
    {
        if (!is_string($password) || $password === '') {
            throw new InvalidArgumentException('Password must be a string and cannot be empty.');
        }

        if (!preg_match('/^\$2[axy]\$(\d\d)\$[\.\/0-9A-Za-z]{22}/', $hash, $matches)
            || $matches[1] < 4
            || $matches[1] > 30
        ) {
            throw new InvalidArgumentException('Hash is invalid.');
        }

        if (function_exists('password_verify')) {
            return password_verify($password, $hash);
        }

        $test = crypt($password, $hash);
        $n = strlen($test);
        if ($n !== 60) {
            return false;
        }

        return $this->compareString($test, $hash);
    }

    public function compareString($expected, $actual)
    {
        if (!is_string($expected)) {
            throw new InvalidArgumentException('Expected expected value to be a string, ' . gettype($expected) . ' given.');
        }

        if (!is_string($actual)) {
            throw new InvalidArgumentException('Expected actual value to be a string, ' . gettype($actual) . ' given.');
        }

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $actual);
        }

        $expected .= "\0";
        $actual .= "\0";
        $expectedLength = StringHelper::byteLength($expected);
        $actualLength = StringHelper::byteLength($actual);
        $diff = $expectedLength - $actualLength;
        for ($i = 0; $i < $actualLength; $i++) {
            $diff |= (ord($actual[$i]) ^ ord($expected[$i % $expectedLength]));
        }

        return $diff === 0;
    }

    protected function generateSalt($cost = 13)
    {
        $cost = (int) $cost;
        if ($cost < 4 || $cost > 31) {
            throw new Exception('Cost must be between 4 and 31.');
        }

        // Get a 20-byte random string
        $rand = $this->generateRandomKey(20);
        // Form the prefix that specifies Blowfish (bcrypt) algorithm and cost parameter.
        $salt = sprintf('$2y$%02d$', $cost);
        // Append the random salt data in the required base64 format.
        $salt .= str_replace('+', '.', substr(base64_encode($rand), 0, 22));

        return $salt;
    }

    private $_useLibreSSL;
    private $_randomFile;

    public function generateRandomKey($length = 32)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('First parameter ($length) must be an integer');
        }

        if ($length < 1) {
            throw new InvalidArgumentException('First parameter ($length) must be greater than 0');
        }

        // always use random_bytes() if it is available
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }

        // The recent LibreSSL RNGs are faster and likely better than /dev/urandom.
        // Parse OPENSSL_VERSION_TEXT because OPENSSL_VERSION_NUMBER is no use for LibreSSL.
        // https://bugs.php.net/bug.php?id=71143
        if ($this->_useLibreSSL === null) {
            $this->_useLibreSSL = defined('OPENSSL_VERSION_TEXT')
                && preg_match('{^LibreSSL (\d\d?)\.(\d\d?)\.(\d\d?)$}', OPENSSL_VERSION_TEXT, $matches)
                && (10000 * $matches[1]) + (100 * $matches[2]) + $matches[3] >= 20105;
        }

        // Since 5.4.0, openssl_random_pseudo_bytes() reads from CryptGenRandom on Windows instead
        // of using OpenSSL library. LibreSSL is OK everywhere but don't use OpenSSL on non-Windows.
        if (function_exists('openssl_random_pseudo_bytes')
            && ($this->_useLibreSSL
            || (
                DIRECTORY_SEPARATOR !== '/'
                && substr_compare(PHP_OS, 'win', 0, 3, true) === 0
            ))
        ) {
            $key = openssl_random_pseudo_bytes($length, $cryptoStrong);
            if ($cryptoStrong === false) {
                throw new Exception(
                    'openssl_random_pseudo_bytes() set $crypto_strong false. Your PHP setup is insecure.'
                );
            }
            if ($key !== false && StringHelper::byteLength($key) === $length) {
                return $key;
            }
        }

        // mcrypt_create_iv() does not use libmcrypt. Since PHP 5.3.7 it directly reads
        // CryptGenRandom on Windows. Elsewhere it directly reads /dev/urandom.
        if (function_exists('mcrypt_create_iv')) {
            $key = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            if (StringHelper::byteLength($key) === $length) {
                return $key;
            }
        }

        // If not on Windows, try to open a random device.
        if ($this->_randomFile === null && DIRECTORY_SEPARATOR === '/') {
            // urandom is a symlink to random on FreeBSD.
            $device = PHP_OS === 'FreeBSD' ? '/dev/random' : '/dev/urandom';
            // Check random device for special character device protection mode. Use lstat()
            // instead of stat() in case an attacker arranges a symlink to a fake device.
            $lstat = @lstat($device);
            if ($lstat !== false && ($lstat['mode'] & 0170000) === 020000) {
                $this->_randomFile = fopen($device, 'rb') ?: null;

                if (is_resource($this->_randomFile)) {
                    // Reduce PHP stream buffer from default 8192 bytes to optimize data
                    // transfer from the random device for smaller values of $length.
                    // This also helps to keep future randoms out of user memory space.
                    $bufferSize = 8;

                    if (function_exists('stream_set_read_buffer')) {
                        stream_set_read_buffer($this->_randomFile, $bufferSize);
                    }
                    // stream_set_read_buffer() isn't implemented on HHVM
                    if (function_exists('stream_set_chunk_size')) {
                        stream_set_chunk_size($this->_randomFile, $bufferSize);
                    }
                }
            }
        }

        if (is_resource($this->_randomFile)) {
            $buffer = '';
            $stillNeed = $length;
            while ($stillNeed > 0) {
                $someBytes = fread($this->_randomFile, $stillNeed);
                if ($someBytes === false) {
                    break;
                }
                $buffer .= $someBytes;
                $stillNeed -= StringHelper::byteLength($someBytes);
                if ($stillNeed === 0) {
                    // Leaving file pointer open in order to make next generation faster by reusing it.
                    return $buffer;
                }
            }
            fclose($this->_randomFile);
            $this->_randomFile = null;
        }

        throw new Exception('Unable to generate a random key');
    }

    protected function generateRandomString($length = 32)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('First parameter ($length) must be an integer');
        }

        if ($length < 1) {
            throw new InvalidArgumentException('First parameter ($length) must be greater than 0');
        }

        $bytes = $this->generateRandomKey($length);
        return substr(StringHelper::base64UrlEncode($bytes), 0, $length);
    }

	private function login()
	{
	   	if($this->get_request_method() != "POST") {
			$this->response('', 406);
		}

		$email = isset($this->_request['email']) ? $this->_request['email'] : "";
		$password = isset($this->_request['password']) ? $this->_request['password'] : "";
			
		if (!$email) {
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}
		
		if(!$password) {
			$error = ['success' => false, "message" => "Password must not be blank."];
			$this->response($this->json($error), 400);
		}

		$stmt = $this->_db->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
		  		$response["success"] = true;
				$response["userId"]  = $row['id'];
				$response["name"] = $row['name'];
				$response["email"] = $row['email'];
				$response["message"] = "Login Successfull";
				$pass = $row["password"];
				if ($this->validatePassword($password, $pass)) { 
	    		 	$this->sessionStart($response['userId'], $response['email'], $response['name']);
					$this->response($this->json($response), 200);
				} else {
	
					$error = ['success' => false, "message" => "Incorrect username or password"];
					$this->response($this->json($error), 400);		
				}
			}	
		}
		$this->response($this->json(['success' => false, "message" => "Incorrect username or password"]), 400);			  
	}
		
	private function sessionStart($userId, $email, $name)
	{
		session_start();	
		$_SESSION["USERID"] = $userId;
		$_SESSION["EMAIL"] = $email;	
		$_SESSION["NAME"] = $name;				
		return true;
	}

    protected function generatePasswordResetToken()
    {
       return $this->generateRandomString() . '_' . time();
    }
	
	private function forgotPassword()
	{ 
		if($this->get_request_method() != "POST") {
			$this->response('', 406);
		}
		$email = isset($this->_request['email']) ? $this->_request['email'] : "";
	   	if (!$email) {
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}

		$stmt = $this->_db->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
				$name = $row["name"];
				$email = $row["email"];	
				$passwordResetToken = $this->generatePasswordResetToken();

				$update = $this->_db->prepare("UPDATE users SET passwordResetToken = ? WHERE id = ?");
				$update->bind_param("si", $passwordResetToken, $row['id']); 
				$update->execute();
				if ($update->affected_rows > 0) {
					$adminEmail = "amaldileep0@gmail.com";
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
					$headers .= 'From: <noreply@example.com>' . "\r\n";
					$subject = "Forget password";
					$to = $email;
					$message = '<html>
							<body>
								<table width="100%" border="0" >		  
								  <tr>
									<td align="center" colspan="3">&nbsp;</td>
								  </tr>	
								  <tr>
									<td align="left" colspan="3">Dear '.ucwords($name).',</td>
								  </tr>	
								  <tr>
									<td align="center" colspan="3">&nbsp;</td>
								  </tr>	
								  <tr>
									<td align="left" colspan="3">You have requested for the Password on APP. Click the below link to reset your password:</td>
								  </tr>	
								  <tr>
									<td align="center" colspan="3">&nbsp;</td>
								  </tr>	
								  <tr>
									<td align="left">http://'.$_SERVER["HTTP_HOST"].'/resetpassword.php?token='.$passwordResetToken.'</td>
								  </tr>	
								  <tr>
									<td align="center" colspan="3">&nbsp;</td>
								  </tr>				   
								  <tr>
									<td colspan="3" align="center">&nbsp;</td>
								  </tr>
								  <tr>
									<td colspan="3" align="center">&copy; All Rights Reserved. APP '.date("Y").'</td>
								  </tr> 	
								  <tr>
									<td colspan="3" align="center">&nbsp;</td>
								  </tr>
								</table>
						</body>
					</html>';
					$sent = mail($to, $subject, $message, $headers,"-f ".$adminEmail."");	
					if ($sent) {
						$result = [];
						$result["success"] = true;
						$result["message"] = "A password reset link is sent to your email";					
						$this->response($this->json($result), 200);
					} 
				} 
				$this->response($this->json(['success' => false, "message" => "Unable to process your request"]), 400);
			}
		} 
		$this->response($this->json(['success' => false, "message" => "We're sorry, Couldn't find user associated with given email"]), 400);
	}
	
	private function resetPassword()
	{
		$token = isset($_GET['token']) ? $_GET['token'] : "";
		$password = isset($this->_request['password']) ? $this->_request['password'] : "";
		$confirmPassword = isset($this->_request['confirmPassword']) ? $this->_request['confirmPassword'] : "";
		if(!$this->validatePasswordResetToken($token)) {
			$error = ['success' => false, "message" => "Invalid password reset token"];
			$this->response($this->json($error), 400);
		}
		
		if(!$password) {
			$error = ['success' => false, "message" => "Password must not be blank."];
			$this->response($this->json($error), 400);
		}
		
		if(!$confirmPassword) {
			$error = ['success' => false, "message" => "Confirm Password must not be blank"];
			$this->response($this->json($error), 400);
		}

		if (strcmp($password, $confirmPassword) !== 0) { 
			$error = ['success' => false, "message" => "Confirm Password must match password."];
			$this->response($this->json($error), 400); 
		}
		$stmt = $this->_db->prepare("SELECT * FROM users WHERE passwordResetToken = ? AND status = 1");
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
				$password = $this->generatePasswordHash($password);
				$update = $this->_db->prepare("UPDATE users SET password = ?, passwordResetToken = null  WHERE id = ? ");
				$update->bind_param("si", $password, $row['id']); 
				$update->execute();
				if ($update->affected_rows > 0) {
					 $this->response($this->json(['success' => true, 'message' => 'Password changed  successfully' ]), 200);	
				}
			}
		}
		$this->response($this->json(['success' => false, 'message' => 'Unable to process your request']), 400);
	}

	protected function validatePasswordResetToken($token)
	{
		if (empty($token) || !is_string($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = 3600;
        return $timestamp + $expire >= time();
	}
	
	private function addRequest()
	{	
		$error = [];
		$fields = [
			'title' => ['required' => true],
			'category' => ['required' => true],
			'initiator' => ['required' => true],
			'initiatorEmail' => ['required' => true],
			'assignee' => ['required' => true],
			'priority' => ['required' => true],
			'requestStatus' => ['required' => true],
			'closedDate' => ['required' => true], 
			'created' => ['required' => true]
		];
		foreach ($fields  as $key => $field) {
            if (array_key_exists($key, $this->_request)) {
            	if(!in_array($key, ['closedDate','created'])) {
            		$fields[$key]['value'] = $this->_request[$key];
            	} else {
            		$fields[$key]['value'] = strtotime($this->_request[$key]);
            	}
            } else if($field['required']) {
            	$error[$key]['message'] = ucwords($key). " must not be blank";
            }
        }
		
		if(!empty($error)) {
			$this->response($this->json(["success" => false,"error" => $error]), 400);	
		}

		if(($priority = $this->getRequestPriority($fields['priority']['value'])) !== null) {
			$fields['priority']['value'] = $priority;
		} else {
			$this->response($this->json(["success" => false, "error" => ['priority' => ['message' => 'Invalid Priority']] ]), 400);		
		}

		if(($status = $this->getRequestStatus($fields['requestStatus']['value'])) !== null) {
			$fields['requestStatus']['value'] = $status;
		} else {
			$this->response($this->json(["success" => false, "error" => ['requestStatus' => ['message' => 'Invalid Status']] ]), 400);	
		}

		$stmt = $this->_db->prepare("INSERT INTO request 
			(
				title,
				category,
				initiator,
				initiatorEmail,
				assignee,
				priority,
				requestStatus,
				created,
				closed
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
				   
		$stmt->bind_param("sssssssii",
			$fields['title']['value'],
			$fields['category']['value'],
			$fields['initiator']['value'],
			$fields['initiatorEmail']['value'],
			$fields['assignee']['value'],
			$fields['priority']['value'],
			$fields['requestStatus']['value'],
			$fields['created']['value'],
			$fields['closedDate']['value']
		);

		$stmt->execute();
		if ($stmt->affected_rows > 0) {
			$result["success"] = true;
			$result["message"] = "Your request has been saved successfully";
			$this->response($this->json($result), 200);	
		}
		$this->response($this->json(['success' => false, 'message' => 'Unable to process your request']), 400);		
	}

	
    protected function getRequestStatus($status = null)
    {	
        $statuses = [
            'CREATED' => 1,
            'ASSIGNED' => 2,
            'CLOSED' => 3
        ];
        return array_key_exists($status, $statuses) ? $statuses[$status] : null;
    }

    protected function getRequestPriority($status = null)
    {	
        $statuses = [
            'HIGH' => 1,
            'NORMAL' => 2,
            'LOW' => 3
        ];
        return array_key_exists($status, $statuses) ? $statuses[$status] : null;
    }
	
	private function updateRequest()
	{
		$error = [];
		$requestId = isset($_GET['id']) ? $_GET['id'] : null;

		if (!$requestId) {
			$this->response($this->json(["success" => false, "error" => 'Unable to find requested resource']), 400);	
		}

		$fields = [
			'title' => ['required' => true],
			'category' => ['required' => true],
			'initiator' => ['required' => true],
			'initiatorEmail' => ['required' => true],
			'assignee' => ['required' => true],
			'priority' => ['required' => true],
			'requestStatus' => ['required' => true],
			'closedDate' => ['required' => true], 
			'created' => ['required' => true]
		];
		foreach ($fields  as $key => $field) {
            if (array_key_exists($key, $this->_request)) {
            	if(!in_array($key, ['closedDate','created'])) {
            		$fields[$key]['value'] = $this->_request[$key];
            	} else {
            		$fields[$key]['value'] = strtotime($this->_request[$key]);
            	}
            } else if($field['required']) {
            	$error[$key]['message'] = ucwords($key). " must not be blank";
            }
        }
		
		if(!empty($error)) {
			$this->response($this->json(["success" => false,"error" => $error]), 400);	
		}

		if(($priority = $this->getRequestPriority($fields['priority']['value'])) !== null) {
			$fields['priority']['value'] = $priority;
		} else {
			$this->response($this->json(["success" => false, "error" => ['priority' => ['message' => 'Invalid Priority']] ]), 400);		
		}

		if(($status = $this->getRequestStatus($fields['requestStatus']['value'])) !== null) {
			$fields['requestStatus']['value'] = $status;
		} else {
			$this->response($this->json(["success" => false, "error" => ['requestStatus' => ['message' => 'Invalid Status']] ]), 400);	
		}

		$stmt = $this->_db->prepare(
			"UPDATE 
				request 
			SET
				title = ?,
				category = ?,
				initiator = ?,
				initiatorEmail = ?,
				assignee = ?,
				priority = ?,
				requestStatus = ?,
				created = ?,
				closed = ?
			WHERE id = ?
			"
		);
				   
		$stmt->bind_param("sssssssiii",
			$fields['title']['value'],
			$fields['category']['value'],
			$fields['initiator']['value'],
			$fields['initiatorEmail']['value'],
			$fields['assignee']['value'],
			$fields['priority']['value'],
			$fields['requestStatus']['value'],
			$fields['created']['value'],
			$fields['closedDate']['value'],
			$requestId
		);

		$stmt->execute();
		if ($stmt->affected_rows > 0) {
			$result["success"] = true;
			$result["message"] = "Your request has been successfully updated";
			$this->response($this->json($result), 200);	
		}
		$this->response($this->json(['success' => false, 'message' => 'Unable to process your request']), 400);		
	}
	
	private function listRequest()
	{
		$stmt = $this->_db->prepare("SELECT * FROM request ORDER BY id DESC");
		$stmt->execute();
		$arr = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$this->response($this->json([ "success" => true ,"data" => $arr]), 200);
	}	
	
	private function deleteRequest()
	{
		$requestId = isset($this->_request['requestId']) ? $this->_request['requestId'] : null;
		
		if(!$requestId){
			$this->response($this->json(['success' => false, "message" => "Request resource not found"]), 400);
		}
		
		$stmt = $this->_db->prepare("DELETE FROM request WHERE requestId = ?");
		$stmt->bind_param("i", $requestId);
		$stmt->execute();
		if($stmt->affected_rows > 0) {
			$this->response($this->json(['success' => true, 'message' => 'Record removed successfully']), 200);	
		} 
		$this->response($this->json(['success' => false, 'message' => 'Unable to process your request']), 400);
	}
	
	private	function sortByOrder($a, $b)
	{
		return $a['addedDate'] - $b['addedDate'];
	}
	
	private function json($data)
	{	
		return is_array($data) ? json_encode($data) : null;
	}

}

$api = new API;
$api->run();

?>