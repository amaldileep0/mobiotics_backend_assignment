## Mobiotics Backend Assignment

A web application to enable basic authentication workflow with simple boostrap,jquery,html. We are going to use PHP API resources to build RESTful API.
We will create Login, Register,Forget Password,Reset Password and Request CRUD API... 

## DIRECTORY STRUCTURE
---------------------
```
api		     contains classes used for REST API
    lib/	 contains dependent 3rd-party packages
    config/  contains shared configurations
website		 contains html files for the Web application 
	css/	 contains application CSS
	js/		 contains application JavaScript

```

## Output
-------------------
![Register API]

Register API: Verb: POST, URL: http://address/api/register

![Login API]

Login API: Verb: POST, URL: http://address/api/signup

![Forget Password API]

Forget Password API : Verb: POST, URL: http://address/api/forgotpassword

![Reset Password API]

Reset Password API : Verb: POST, URL: http://address/api/resetpassword

![List Request API]

List Request API : Verb: GET, URL: http://address/api/listrequest

![Create Request API]

Create Request API : Verb: POST, URL: http://address/api/addrequest

![Update Request API]

Update Request API : Verb: POST, URL: http://address/api/updaterequest

![Delete Request API]

Delete Request API : Verb: POST, URL: http://address/api/deleterequest

![Get Request API]

Get Request API : Verb: GET, URL: http://address/api/getrequest

### Prerequisites
 * PHP version 5.6 or above.
 * Mysql version 5.6 or above.

###Configuration Files

All of the configuration files for the backend application are stored in the config directory inside api directory.

### Deployment

* The next thing you should do is to set application config.json file in config directory of api, except this no other configuration required for backend.
* You may also want to set backend api url's in js files inside directoty web > js.



