<?php

namespace Typemill\Models;

use Typemill\Models\User;
use Valitron\Validator;

class Validation
{
	/**
	* Constructor with custom validation rules 
	*
	* @param obj $db the database connection.
	*/

	public function __construct()
	{
		$user = new User();
		
		Validator::langDir(__DIR__.'/../vendor/vlucas/valitron/lang'); // always set langDir before lang.
		Validator::lang('en');

		Validator::addRule('userAvailable', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($value);
			if($userdata){ return false; }
			return true;
		}, 'taken');

		Validator::addRule('userExists', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($value);
			if($userdata){ return true; }
			return false;
		}, 'does not exist');
		
		Validator::addRule('checkPassword', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($fields['username']);
			if($userdata && password_verify($value, $userdata['password'])){ return true; }
			return false;
		}, 'wrong password');
		
		Validator::addRule('emailAvailable', function($field, $value, array $params, array $fields)
		{
			$email = 'testmail@gmail.com';
			if($email){ return false; }
			return true;
		}, 'taken');

		Validator::addRule('emailKnown', function($field, $value, array $params, array $fields)
		{
			$email = 'testmail@gmail.com';
			if(!$email){ return false; }
			return true;
		}, 'unknown');
		
		Validator::addRule('noHTML', function($field, $value, array $params, array $fields)
		{
			if ( $value == strip_tags($value) )
			{
				return true;
			}
			return false;
		}, 'contains html');
	}

	/**
	* validation for signup form
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function signin(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'password'])->message("Required");
		$v->rule('alphaNum', 'username')->message("Invalid characters");
		$v->rule('lengthBetween', 'password', 5, 20)->message("Length between 5 - 20");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20");
		
		if($v->validate())
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* validation for signup form
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function newUser(array $params, $userroles)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'email', 'password'])->message("required");
		$v->rule('alphaNum', 'username')->message("invalid characters");
		$v->rule('lengthBetween', 'password', 5, 20)->message("Length between 5 - 20");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 
		$v->rule('userAvailable', 'username')->message("User already exists");
		$v->rule('email', 'email')->message("e-mail is invalid");
		$v->rule('in', 'userrole', $userroles);
		
		return $this->validationResult($v);
	}
	
	public function existingUser(array $params, $userroles)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'email', 'userrole'])->message("required");
		$v->rule('alphaNum', 'username')->message("invalid");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 
		$v->rule('userExists', 'username')->message("user does not exist");
		$v->rule('email', 'email')->message("e-mail is invalid");
		$v->rule('in', 'userrole', $userroles);

		return $this->validationResult($v);		
	}
	
	public function username($username)
	{
		$v = new Validator($username);
		$v->rule('alphaNum', 'username')->message("Only alpha-numeric characters allowed");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 

		return $this->validationResult($v);
	}

	/**
	* validation for changing the password
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function newPassword(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['password', 'newpassword']);
		$v->rule('lengthBetween', 'newpassword', 5, 20);
		$v->rule('checkPassword', 'password')->message("Password is wrong");
		
		return $this->validationResult($v);
	}

	/**
	* validation for basic settings input
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/

	public function settings(array $params, array $copyright, $name = false)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['title', 'author', 'copyright', 'year']);
		$v->rule('lengthBetween', 'title', 2, 20);
		$v->rule('lengthBetween', 'author', 2, 40);
		$v->rule('regex', 'title', '/^[\pL0-9_ \-]*$/u');
		$v->rule('regex', 'author', '/^[\pL_ \-]*$/u');
		$v->rule('integer', 'year');
		$v->rule('length', 'year', 4);
		$v->rule('in', 'copyright', $copyright);
		
		return $this->validationResult($v, $name);
	}
	
	public function objectField($fieldName, $fieldValue, $pluginName, $fieldDefinitions)
	{	
		$v = new Validator(array($fieldName => $fieldValue));

		
		if(isset($fieldDefinitions['required']))
		{
			$v->rule('required', $fieldName);
		}
		
		switch($fieldDefinitions['type'])
		{
			case "select":
				/* create array with option keys as value */
				$options = array();
				foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }
				$v->rule('in', $fieldName, $options);
				break;
			case "radio":
			case "checkboxlist":
				$v->rule('in', $fieldName, $fieldDefinitions['options']);
				break;
			case "color":
				$v->rule('regex', $fieldName, '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/');
				break;
			case "email":
				$v->rule('email', $fieldName);
				break;
			case "date":
				$v->rule('date', $fieldName);
				break;
			case "checkbox":
				$v->rule('accepted', $fieldName);
				break;
			case "url":
				$v->rule('lengthMax', $fieldName, 200);
				$v->rule('url', $fieldName);
				break;
			case "text":
				$v->rule('lengthMax', $fieldName, 200);
				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-\.\?\!]*$/u');
				break;
			case "textarea":
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('noHTML', $fieldName);
				// $v->rule('regex', $fieldName, '/<[^<]+>/');
				break;
			default:
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-]*$/u');				
		}
		
		return $this->validationResult($v, $pluginName);
	}
	
	/**
	* result for validation
	* 
	* @param obj $v the validation object.
	* @return bool
	*/
	
	public function validationResult($v, $name = false)
	{
		if($v->validate())
		{
			return true;
		}
		else
		{
			if($name)
			{
				if(isset($_SESSION['errors'][$name]))
				{
					foreach ($v->errors() as $key => $val)
					{
						$_SESSION['errors'][$name][$key] = $val;
						break;
					}
				}
				else
				{
					$_SESSION['errors'][$name] = $v->errors();
				}
			}
			else
			{
				$_SESSION['errors'] = $v->errors();
			}
			return false;
		}
	}
}