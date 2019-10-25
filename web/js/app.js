$(function () {
    "use strict";
    //div
	var loginDiv = $('#_login');
	var registerDiv = $('#_register');
	var forgetPasswordDiv = $('#_forget_password');
	var resetPasswordDiv = $('#_reset_password');
    var ajaxBaseUrl = "http://13.59.231.78/mobiotics_backend_assignment/api/";

    $('#login-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "login",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    localStorage.setItem("_identity", JSON.stringify(response.user));
                    window.location.replace("dashboard.html");
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
                    window.location.replace("index.html");
                } else {
                    var error = (response.message) ? response.message : (response.error) ? response.error : "Something went wrong";
                    showErrors(error)
                }
            },
            error: function(er) {

            }
       });
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
                    document.getElementById('forget-password-form').reset();
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
});