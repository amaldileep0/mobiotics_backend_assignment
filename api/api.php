<?php

header("Content-type: application/json");
header("Access-Control-Allow-Origin: *");

require_once("Rest.inc.php");

class API extends REST {
	public $data = "";

	const DB_SERVER 	= "localhost";
	const DB_USER 		= "root";
	const DB_PASSWORD 	= "";
	const DB 			= "amal";		

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
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}			
	}

	public function processApi()
	{ 
		$action = trim($_REQUEST['type'];
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
			$error = [];
			$error = ['success' => false, "message" => "Name must not be blank."];
			$this->response($this->json($error), 400);
		}
	
		if(!$email) {
			$error = [];
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  			$error = [];
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}
		
		if(!$password) {
			$error = [];
			$error = ['success' => false, "message" => "Password must not be blank."];
			$this->response($this->json($error), 400);
		}
		
		if(!$confirmPassword) {
			$error = [];
			$error = ['success' => false, "message" => "Confirm Password must not be blank"];
			$this->response($this->json($error), 400);
		}

		if (strcmp($password, $confirmPassword) !== 0) { 
    		$error = [];
			$error = ['success' => false, "message" => "Confirm Password must match password."];
			$this->response($this->json($error), 400); 
		} 
		
		$query = $this->_db->prepare("SELECT * FROM users WHERE email = ?");
		$query->bind_param("s", $email);
		$query->execute();
		$res = $query->get_result();
	
		if($res->num_rows > 0) {
			$error = [];
			$error = ['success' => false, "message" => "The given Email already exists"];
			$this->response($this->json($error), 400);
		} else {
			$stmt = $this->_db->prepare("INSERT INTO users (name, email,password) VALUES (?, ?, ?)");
			$stmt->bind_param("sss", $name, $email, $password); 
			$stmt->execute();
			$result["message"] = "Registration successfull. Please login to continue.";	
			$this->response($this->json($result), 200);
		}
		$this->response($this->json(["success" => false, "message" => "Internal server error"]), 500);
	}

	private function login()
	{
	   	if($this->get_request_method() != "POST") {
			this->response('', 406);
		}
		$email = isset($this->_request['email']) ? $this->_request['email'] : "";
		$password = isset($this->_request['password']) ? $this->_request['password'] : "";
			
		if (!$email) {
			$error = [];
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  			$error = [];
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}
		
		if(!$password) {
			$error = [];
			$error = ['success' => false, "message" => "Password must not be blank."];
			$this->response($this->json($error), 400);
		}				  

		$stmt = $this->_db->prepare("SELECT * FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
		  		$result["success"] = true;
				$result["userId"]  = $row['userId'];
				$result["name"] = $row['name'];
				$result["email"] = $row['email'];
				$result["message"] = "Login Successfull";
				$pass = $row["password"];
				
				if (strcmp($password, $confirmPassword) == 0) { 
	    		 	$this->sessionStart($result['userId'], $result['email'], $result['name']);
					$this->response($this->json($result), 200);
				} else {
					$error = [];
					$error = ['success' => false, "message" => "Incorrect usernamr or password"];
					$this->response($this->json($error), 400);		
				}
			}	
		} 			  
		
		$this->response($this->json(['success' => false, "message" => "Incorrect usernamr or password"]), 400);			  
	}
		
	private function sessionStart($userId, $email, $name)
	{
		session_start();	
		$_SESSION["USERID"] = $userId;
		$_SESSION["EMAIL"] = $email;	
		$_SESSION["NAME"] = $name;				
		return true;
	}	
	
	private function forgotPassword()
	{
		
		$email = isset($this->_request['email']) ? $this->_request['email'] : "";
	   	
	   	if (!$email) {
			$error = [];
			$error = ['success' => false, "message" => "Email must not be blank."];
			$this->response($this->json($error), 400);
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  			$error = [];
			$error = ['success' => false, "message" => "Invalid Email"];
			$this->response($this->json($error), 400);
		}

		$stmt = $this->_db->prepare("SELECT * FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
		  		$userId	= $row["userId"];
				$name = $row["name"];
				$email = $row["email"];	
				$userIdE = urlencode(base64_encode($userId));
				$adminEmail = "amaldileep0@gmail.com";
				$headers = "From: APP TEST\r\n";
				$subject = "Forget password";
				$to = $email;
				$message = '<html>
						<body>
							<table width="100%" border="0" >		  
							  <tr>
								<td align="center" colspan="3">&nbsp;</td>
							  </tr>	
							  <tr>
								<td align="left" colspan="3">Dear '.trim($name).',</td>
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
								<td align="left">http://'.$_SERVER["HTTP_HOST"].'/reset-password.php?userIdE='.$userIdE.'</td>
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
				} else {
					$this->response($this->json(['success' => false, "message" => "Unable to process your request"]), 400);
				}
			}
		} 
		$this->response($this->json(['success' => false, "message" => "We're sorry, Couldn't find user associated with given email"]), 400);
	}
	
	private function resetPassword()
	{
		
		if(trim($this->_request['userId']) != ""){
			$userId = $this->_request['userId'];
		}
		if(trim($this->_request['password']) != ""){
			$password = $this->_request['password'];
		}
		if(trim($this->_request['confirmPassword']) != ""){
			$confirmPassword = $this->_request['confirmPassword'];
		} 
		
		if(trim($userId) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "User cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($password) == "") {
			$error = array();
			$error = array('success' => "0", "message" => "Password cannotbe null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($confirmPassword) == "") {
			$error = array();
			$error = array('success' => "0", "message" => "Confirm Password cannotbe null!");
			$this->response($this->json($error), 400);
		}				  
	
		if(trim($password) <> trim($confirmPassword) ) {
			
			$error = array();
			$error = array('success' => "0", "message" => "Password mismatch!");
			$this->response($this->json($error), 400);
			
		}

		$query    = "UPDATE 
							users 
						SET 
							password = \"$password\"
						WHERE 
							userId   = $userId";
						   
		$sql = mysqli_query($this->_db,$query);;
		
		if($sql){
			$result["success"] 		= 1;
			$result["message"] = "Thank You! password changed  successfully!";
			$this->response($this->json($result), 200);	
		}
		else{
			$error = array();
			$error = array('success' => "0", "message" => "Failure Occurs");
			$this->response($this->json($error), 400);
		}			
		
	}
	
	private function addRequest(){

		if(trim($this->_request['userId']) != ""){
			$userId = $this->_request['userId'];
		}
		if(trim($this->_request['title']) != ""){
			$title = $this->_request['title'];
		}
		if(trim($this->_request['category']) != ""){
			$category = $this->_request['category'];
		}
		if(trim($this->_request['initiator']) != ""){
			$initiator = $this->_request['initiator'];
		}
		if(trim($this->_request['initiatorEmail']) != ""){
			$initiatorEmail = $this->_request['initiatorEmail'];
		}
		if(trim($this->_request['assignee']) != ""){
			$assignee = $this->_request['assignee'];
		}
		if(trim($this->_request['priority']) != ""){
			$priority = $this->_request['priority'];
		}
		if(trim($this->_request['status']) != ""){
			$status = $this->_request['status'];
		}
		if(trim($this->_request['createdDate']) != ""){
			$createdDate = $this->_request['createdDate'];
		}
		if(trim($this->_request['closedDate']) != ""){
			$closedDate = $this->_request['closedDate'];
		}
		
		if(trim($userId) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "User cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($title) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "Title cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($category) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "Category cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($initiator) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Initiator cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($initiatorEmail) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Initiator Email cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($assignee) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Assignee cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($priority) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Priority cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($status) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Status cannot be null!");
				$this->response($this->json($error), 400);
		}
		
		if(trim($createdDate) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Created Date cannot be null!");
				$this->response($this->json($error), 400);
		}
		
		if(trim($closedDate) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Closed Date cannot be null!");
				$this->response($this->json($error), 400);
		}
		
		if($createdDate <> ""){
			$createdDate = $this->convertdatemysql($createdDate);
		}
		
		if($closedDate <> ""){
			$closedDate = $this->convertdatemysql($closedDate); 
		}
		
		
		$title 			= addslashes($title);
		$category 		= addslashes($category);
		$initiator 		= addslashes($initiator);
		$initiatorEmail = addslashes($initiatorEmail);
		$assignee 		= addslashes($assignee);
	
		$query    = "INSERT INTO   
							request 
							(
							userId,
							title,
							category,
							initiator,
							initiatorEmail,
							assignee,
							priority,
							status,
							createdDate,
							closedDate
							) 
						VALUES 
							(
							\"$userId\",
							\"$title\",
							\"$category\",
							\"$initiator\",
							\"$initiatorEmail\",
							\"$assignee\",
							\"$priority\",
							\"$status\",
							\"$createdDate\",
							\"$closedDate\"

							)";
								
						   
		$sql = mysqli_query($this->_db,$query);
		
		if($sql){
			$result["success"] 		= 1;
			$result["message"] = "Thank You! your request has been sent to the company!";
			$this->response($this->json($result), 200);	
		}
		else{
			$error = array();
			$error = array('success' => "0", "message" => "Failure Occurs" );
			$this->response($this->json($error), 400);
		}			
		
	}
	
	private function updateRequest(){

		if(trim($this->_request['requestId']) != ""){
			$requestId = $this->_request['requestId'];
		}
		if(trim($this->_request['title']) != ""){
			$title = $this->_request['title'];
		}
		if(trim($this->_request['category']) != ""){
			$category = $this->_request['category'];
		}
		if(trim($this->_request['initiator']) != ""){
			$initiator = $this->_request['initiator'];
		}
		if(trim($this->_request['initiatorEmail']) != ""){
			$initiatorEmail = $this->_request['initiatorEmail'];
		}
		if(trim($this->_request['assignee']) != ""){
			$assignee = $this->_request['assignee'];
		}
		if(trim($this->_request['priority']) != ""){
			$priority = $this->_request['priority'];
		}
		if(trim($this->_request['status']) != ""){
			$status = $this->_request['status'];
		}
		if(trim($this->_request['createdDate']) != ""){
			$createdDate = $this->_request['createdDate'];
		}
		if(trim($this->_request['closedDate']) != ""){
			$closedDate = $this->_request['closedDate'];
		}
		
		if(trim($requestId) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "request Id cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($title) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "Title cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($category) == ""){
			$error = array();
			$error = array('success' => "0", "message" => "Category cannot be null!");
			$this->response($this->json($error), 400);
		}
		
		if(trim($initiator) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Initiator cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($initiatorEmail) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Initiator Email cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($assignee) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Assignee cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($priority) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Priority cannot be null!");
				$this->response($this->json($error), 400);
		}
		if(trim($status) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Status cannot be null!");
				$this->response($this->json($error), 400);
		}
		
		if(trim($closedDate) == ""){
				$error = array();
				$error = array('success' => "0", "message" => "Closed Date cannot be null!");
				$this->response($this->json($error), 400);
		}
		
		
		if($closedDate <> ""){
			$closedDate = $this->convertdatemysql($closedDate); 
		}
		
		
		$title 			= addslashes($title);
		$category 		= addslashes($category);
		$initiator 		= addslashes($initiator);
		$initiatorEmail = addslashes($initiatorEmail);
		$assignee 		= addslashes($assignee);
	
		$query    = "UPDATE 
						request 
					SET 
						title 			= \"$title\",
						category 		= \"$category\",
						initiator 		= \"$initiator\",
						initiatorEmail 	= \"$initiatorEmail\",
						assignee 		= \"$assignee\",
						priority 		= \"$priority\",
						status 			= \"$status\",
						closedDate 		= \"$closedDate\"
					WHERE 
						requestId   = $requestId";
								
						   
		$sql = mysqli_query($this->_db,$query);
		
		if($sql){
			$result["success"] 		= 1;
			$result["message"] = "Thank You! your request has been updated!";
			$this->response($this->json($result), 200);	
		}
		else{
			$error = array();
			$error = array('success' => "0", "message" => "Failure Occurs" );
			$this->response($this->json($error), 400);
		}			
		
	}
	
	private function listRequest(){
		
		if(trim($this->_request['userId']) != ""){
			$userId = $this->_request['userId'];
		}
		
		if(trim($this->_request['isLimit']) != ""){
			$isLimit = $this->_request['isLimit'];
		}

          $query    = "SELECT	
							*
						FROM	
							request 
						WHERE 
							1=1 ";     

		if(trim($userId) != ""){
			$query .= " AND userId = ".trim($userId);
		}
		
		if(trim($isLimit) != ""){
			
	    	$query .= " LIMIT 0,".trim($isLimit); 
	
		}
		
		
			
		$sql = mysqli_query($this->_db,$query);

		if(mysqli_num_rows($sql) > 0){	

			$result = array();
			$datas = array();
			$result["success"] = 1;
	
				while($rlt = mysqli_fetch_array($sql,MYSQLI_ASSOC)){	
				
	
									
							$datas[$rlt["requestId"]] = array(						
														"requestId"         	=> $rlt["requestId"],
														"userId"         		=> $rlt["userId"],
														"title"       			=> stripslashes($rlt["title"]),
														"category"        		=> stripslashes($rlt["category"]),
														"initiator"       		=> stripslashes($rlt["initiator"]),
														"initiatorEmail"       	=> stripslashes($rlt["initiatorEmail"]),
														"assignee"       		=> stripslashes($rlt["assignee"]),
														"priority"       		=> $rlt["priority"],
														"status"       			=> $rlt["status"],
														"createdDate"       	=> $rlt["createdDate"],
														"closedDate"      		 => $rlt["closedDate"]
					);

				}
				
				
		   $datas = $this->multid_sort($datas, 'requestId');		

			$result["data"] 	= $datas;
			$result["message"]  = "Available Requests!";
			$this->response($this->json($result), 200);		

		}
		else{
				$error = array();
				$error = array('success' => "0", "message" => "Currently there are no requests available!");
				$this->response($this->json($error), 400);
		}

	}	
	
	private function deleteRequest()
	{

		$requestId = isset($this->_request['requestId']) ? $this->_request['requestId'] : null;
		
		if(!$requestId){
			$this->response(
				$this->json(['success' => false, "message" => "Request resource not found"],
				400
			);
		}
		
		$stmt = $this->_db->prepare("DELETE FROM request WHERE requestId = ?");
		$stmt->bind_param("i", $requestId);
		$stmt->execute();
		if($stmt->affected_rows > 0) {
			$this->response($this->json(['success' => true, 'message' => 'Record removed successfully']), 200);	
		} 
		$this->response($this->json(['success' => false, 'message' => 'Unable to process your request']), 400);
	}
	
	private function convertdatemysql($date2)
	{
		$dateother = explode("/",$date2);
		if ($dateother[2] != "") {
			$date1  =$dateother[2].'-'.$dateother[0].'-'.$dateother[1];
		} else {
			return $date2;
		}
		return $date1;
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
$api->processApi();
?>