$(function () {
    "use strict";
    var ajaxBaseUrl = "http://13.59.231.78/mobiotics_backend_assignment/api/";

    function getQueryParams() {
	    var params = {},
	    pairs = document.URL.split('?').pop().split('&');
	    for (var i = 0, p; i < pairs.length; i++) {
	        p = pairs[i].split('=');
	        params[ p[0] ] =  p[1];
	    }     
	    return params;
   	}

   	$('#reset-password-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "resetpassword?token=" + $('#reset-token').val(),
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    var message = (response.message) ? response.message : "Password successfully saved. Please go to login";
                    showSuccess(message);
                    document.getElementById('reset-password-form').reset();
                } else {
                    var error = (response.message) ? response.message : (response.error) ? response.error : "Something went wrong";
                    showErrors(error)
                }
            },
            error: function(er) {

            }
       });
    });

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

   	$(document).ready(function() {
	  	var queryParameter =  getQueryParams();
	  	if (queryParameter.hasOwnProperty('token') != undefined && !_.isEmpty(queryParameter.token)) {
	  		$('#reset-token').val(queryParameter.token)
	  	}	
	});
});