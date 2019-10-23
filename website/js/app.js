$(function () {
    "use strict";
    //div
	var loginDiv = $('#_login');
	var registerDiv = $('#_register');
	var forgetPasswordDiv = $('#_forget_password');
	var resetPasswordDiv = $('#_reset_password');
	var requestListingDiv = $('#_request_listing');
	var createRequestDiv  = $('#_create_request');

    var ajaxBaseUrl = "http://localhost/mb/mobiotics_backend_assignment/api/";

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

    $('#login-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "login",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    localStorage.setItem("_identity", JSON.stringify(response.user));
                    loginDiv.hide();
                    showDashboard();
                } else {
                    var error = (response.message) ? response.message : (response.error) ? response.error : "Something went wrong";
                    showErrors(error)
                }
            },
            error: function(er) {

            }
       });
     });

    $('#register-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "signup",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    var message = (response.message) ? response.message : "Successfully processed your request";
                    showSuccess(message);
                    loginDiv.show();
                    resetForm('login-form')
                    registerDiv.hide();
                } else {
                    var error = (response.message) ? response.message : (response.error) ? response.error : "Something went wrong";
                    showErrors(error)
                }
            },
            error: function(er) {

            }
       });
    });

    function showDashboard()
    {   
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "listrequest",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined') {
                    requestListingDiv.show();
                    var content = '';
                    if (response.success) {
                        var data = response.data;
                        for (var i = 0; i < data.length; i++) {
                            content += '<tr>';
                            content += '<td>' + data[i].id + '</td>';
                            content += '<td>' + data[i].title + '</td>';
                            content += '<td>' + data[i].category + '</td>';
                            content += '<td>' + data[i].initiator + '</td>';
                            content += '<td>' + data[i].initiatorEmail + '</td>';
                            content += '<td>' + data[i].assignee + '</td>';
                            content += '<td>' + data[i].priority + '</td>';
                            content += '<td>' + data[i].requestStatus + '</td>';
                            content += '<td>' + data[i].created + '</td>';
                            content += '<td>' + data[i].closed + '</td>';
                            content += '<td><a href="#" id="edit-request" data-id="'+ data[i].id +'">Update</a> <a href="#" id="delete-request" data-id="'+ data[i].id +'">Delete</a></td>';
                            content += '</tr>';
                        }
                    } else {
                        content += '<tr>';
                        content += '<td colspan="11" style="text-align:center;">No records found</td>';
                        content += '</tr>';
                    }
                    $('#request-listing-table tbody').html(content);
                }
            },
            error: function(er) {

            }
        });
        requestListingDiv.show();
    }

    $('#delete-request').click(function(event) {
        event.preventDefault();
        alert('hai')
        var id = $(this).attr("data-id");
        console.log(id)
    });

    $('#forget-password-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "forgotpassword",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    var message = (response.message) ? response.message : "An email is sent to your mail.Please follow the instruction.";
                    showSuccess(message);
                    loginDiv.show()
                    forgetPasswordDiv.hide();
                    resetForm('login-form')
                } else {
                    var error = (response.message) ? response.message : (response.error) ? response.error : "Something went wrong";
                    showErrors(error)
                }
            },
            error: function(er) {

            }
       });
    });

	function resetForm(formId) {
        var arr = ['login-form', 'register-form', 'forget-password-form', 'reset-password-form'];
        for (var key in arr) {
            if (arr[key] != formId) {
                document.getElementById(arr[key]).reset();
            }
        }
	}

    function showErrors(response)
    {
        clearMessage();
        var errors = response
        if (errors) {
            if (_.isString(errors))
                errors = [errors];

            $('.error-msg').show();
            var str = "";
            errors.forEach(function(error) {
                var splitted = error.split("\n");
                splitted.forEach(function(msg) {
                    str += '<li>' + msg + '</li>'
                });
            });
            $('#errors').html('<p>There were some error.</p>').append('<ul>' + str + '</ul>');
        }
    }

    function showSuccess(response)
    {
        clearMessage();
        var success = response
            if (success) {

                if (_.isString(success))
                    success = [success];

                $('.success-msg').show();
                var str = "";
                success.forEach(function(error) {
                    var splitted = error.split("\n");
                    splitted.forEach(function(msg) {
                        str += '<li>' + msg + '</li>'
                    });
                });
                $('#success').html('Success!').append('<ul>' + str + '</ul>');
            }
    }

    function clearMessage() {
        $('#errors').empty();
        $('.error-msg').hide();
        $('#success').empty();
        $('.success-msg').hide();
    }

    $( document ).ready(function() {
    	$( "#closed" ).datepicker();
    	$( "#created" ).datepicker();
	});
});