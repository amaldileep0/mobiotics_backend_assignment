$(function () {
    "use strict";
    //div
	var loginDiv = $('#_login');
	var registerDiv = $('#_register');
	var forgetPasswordDiv = $('#_forget_password');
	var resetPasswordDiv = $('#_reset_password');
	var requestListingDiv = $('#_request_listing');
	var createRequestDiv  = $('#_create_request');

	$('.register_link').click(function(event) {
    	event.preventDefault();
    	registerDiv.show();
        resetForm('register-from')
    	loginDiv.hide();
    	forgetPasswordDiv.hide();
    	resetPasswordDiv.hide();
    	requestListingDiv.hide();
    	createRequestDiv.hide();
	});

	$('.login_link').click(function(event) {
    	event.preventDefault();
    	loginDiv.show();
        resetForm('login-form')
    	registerDiv.hide();
    	forgetPasswordDiv.hide();
    	resetPasswordDiv.hide();
    	requestListingDiv.hide();
    	createRequestDiv.hide();
	});

	$('.forget_password_link').click(function(event) {
    	event.preventDefault();
    	forgetPasswordDiv.show();
        resetForm('forget-password-form')
    	loginDiv.hide();
    	registerDiv.hide();
    	resetPasswordDiv.hide();
    	requestListingDiv.hide();
    	createRequestDiv.hide();
	});

	function resetForm(formId) {
        var arr = ['login-form', 'register-form', 'forget-password-form'];
        for (var key in arr) {
            if (arr[key] != formId) {
                document.getElementById(arr[key]).reset();
            }
        }
	}

    $( document ).ready(function() {
    	$( "#closed" ).datepicker();
    	$( "#created" ).datepicker();
	});
});