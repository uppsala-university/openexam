// JavaScript Document specific to exam/index
// @Author Ahsan Shahzad [MedfarmDoIT]
		
		
	/*-- Event handlers --*/
        $(document).ready(function() {
				
		$('.manage-students').click(function() {
			
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
/*							buttons: {
							"Save": function() {
										addQuestPartTab();
							},
							"I am done, save this question": function() {
										
										// add question to qsJson and in database
										saveQuestionToExam(qId);
										
										// close popup window
										$(this).dialog('destroy');
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
				
	});
	
