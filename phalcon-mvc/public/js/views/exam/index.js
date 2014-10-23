// JavaScript Document specific to exam/index
// @Author Ahsan Shahzad [MedfarmDoIT]
		
		
	/*-- Event handlers --*/
        $(document).ready(function() {
				
		$(document).on('click', '.manage-students', function() {
			
			$.ajax({
				type: "POST",
				data: {'exam_id': $(this).attr('data-id')},
				url: baseURL + 'exam/students/',
				success: function(content) {
					$("#mng-students").html(content);
					$("#mng-students").dialog({
						autoOpen: true,
						width: "50%",
						position:  ['center',20],
						modal: true,
/*						buttons: {
							"Save": function() {
							},
							"btn ": function() {
							},
							Cancel: function() {
								$(this).dialog('destroy');
							}
						},*/
						close: function() {
							$(this).dialog('destroy');
						},
						show: {
							effect: "blind",
							duration: 5
						},
						hide: {
							effect: "blind",
							duration: 5
						}
					});
				}
			});
		});

		$(document).on('click', '.reuse-exam', function() {
			var examId = $(this).closest('.list-group-item').attr('data-id');
			$("#reuse-exam").dialog({
				autoOpen: true,
				modal: true,
				buttons: {
					"Proceed further »": function() {
						
						var data = { 'options[]' : []};
						$('.exam-reuse-opt').filter(':checked').each(function() {
							data['options[]'].push($(this).val());
						});	
						$.ajax({
							type: "POST",
							data: data,
							url: baseURL + 'exam/replicate/' + examId,
							success: function(response) {
								resp = jQuery.parseJSON(response);
								if(resp.status == 'success') {
									location.href = baseURL + 'exam/update/' + resp.exam_id;
								}
							}
						});
					},
					Cancel: function() {
						$(this).dialog('destroy');
					}
				},
				close: function() {
					$(this).dialog('destroy');
				}
			});
			
		});
		
		$(document).on('click', '.del-exam', function() {
			var examLine = $(this).closest('.list-group-item');
			var examId = $(examLine).attr('data-id');
			var examName = $(examLine).find('.exam-name').html();
			
			if(confirm("Are you sure you want to delete this Exam: '"+examName+"'")) {
				ajax(
					baseURL + 'core/ajax/creator/exam/delete',
					{"id": examId},
					function (examData) {
						$(examLine).slideUp(500, function() {
							$(this).remove();
						});
					}
				);
				
			}
		});
		
	});
	
