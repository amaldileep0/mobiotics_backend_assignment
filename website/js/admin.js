$(function () {
    "use strict";
    //div
	var requestListingDiv = $('#_request_listing');
	var createRequestDiv  = $('#_create_request');

    var ajaxBaseUrl = "http://api.ass.com/";
    var homeUrl = "http://ass.com/"


    function getRequestData()
    {   
        $.ajax({
            type: "GET",
            url: ajaxBaseUrl + "listrequest",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined') {
                    var content = '';
                    if (response.success && response.data.length > 0) {
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
                            content += '<td><button type="button" class="btn btn-primary btn-sm" id="edit-request" data-toggle="modal" data-id="'+ data[i].id +'">Update</button> <a href="#" id="delete-request" role="button" class="btn btn-danger btn-sm" data-id="'+ data[i].id +'">Delete</a></td>';
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
    }

    $(document).on("click", "#delete-request", function() {
        $.post(ajaxBaseUrl + "deleterequest",
        {
            id: $(this).data("id")
        },
        function(data, status) {
            if (data.success) {
                location.reload();
            }
        });
    });

    $(document).on("click", "#edit-request", function() {
        var url = ajaxBaseUrl + "getRequest?id="+$(this).data("id")
        $("form#create-request-form").prop('id','edit-request-form');
        $('.modal-title').text('Update Request')
        $(".request-form-submit").html('Update');
        $('<input>').attr({type: 'hidden',id: 'request-id', name: 'id'}).appendTo('#edit-request-form');
        $.get( url, function( response ) {
            if (response.success) {
                $.each(response.data, function (key, val) {
                    $('#title').val('tst')
                    $("input[name='"+ key +"']").val(val);
                });
                $('#createRequestModal').modal('show');
            }
        });
    });

    $("#createRequestModal").on("hidden.bs.modal", function () {
        document.getElementById('edit-request-form').reset();
        $('#request-id').remove();
        $("form#create-request-form").prop('id', 'create-request-form');
        $('.modal-title').text('Create New Request')
        $(".request-form-submit").html('Submit');
    });

    $('#create-request-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: ajaxBaseUrl + "addrequest",
            data: $(this).serialize(),
            success: function(response) {
                if (typeof(response) !== 'undefined' && response.success) {
                    var message = (response.message) ? response.message : "Successfully processed your request";
                    window.location.replace(homeUrl + "dashboard.html");
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
        var errors = response.message
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

    $( document ).ready(function() {
        getRequestData();
        $( "#closed" ).datepicker();
        $( "#created" ).datepicker();
    });

    function clearMessage() {
        $('#errors').empty();
        $('.error-msg').hide();
        $('#success').empty();
        $('.success-msg').hide();
    }
});