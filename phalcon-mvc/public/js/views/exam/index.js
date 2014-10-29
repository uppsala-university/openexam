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
		

		$(document).on('keyup', '.exam-search-box', function(e) {
			
			if($(this).val() == '') {
				var examListingAreas = $(this).closest('.exam-listing-wrapper').find('.exam-listing-area');
				if(examListingAreas.length > 1) {
					$(examListingAreas).not(':last').remove();
				}
			} else if(e.which == 13) {
				$(this).parent().find('.search-exam').trigger('click');
			}
		});
		
		$(document).on('click', '.search-exam', function() {
			
			reloadExamList($(this), 0);
			
		});
		
		
		$(document).on('change', '.exam-sort-by', function() {
			
			reloadExamList($(this));
			
		});

		$(document).on('click', '.exam-sort-order', function() {
			if($(this).hasClass('fa-arrow-circle-down')) {
				$(this).removeClass('fa-arrow-circle-down').addClass('fa-arrow-circle-up');
				$(this).attr('order', 'asc');
			} else {
				$(this).removeClass('fa-arrow-circle-up').addClass('fa-arrow-circle-down');
				$(this).attr('order', 'desc');				
			}
			
			reloadExamList($(this));
		});
		
		$(document).on('click', '.pagination > li', function() {
			
			$(this).closest('.pagination').find('li').removeClass('active');
			$(this).addClass('active');
			
			reloadExamList($(this));
			
			return false;
		});

		var reloadExamList = function (element, offset) {

			// section
			var section = $(element).closest('.exam-listing-wrapper');
			var role = $(section).attr('exam-role');
			var examSortBy = $(section).find('.exam-sort-by').val();
			var examSortOrder = $(section).find('.exam-sort-order').attr('order');
			var searchKey = $(section).find('.exam-search-box').val();
			if(searchKey) {
				var cond = ["name like :key:", {"key":"%"+searchKey+"%"}];
			} else {
				var cond = [];
			}
			
			if(typeof offset === 'undefined') {
				offset = $(section).find('.pagination > .active').attr('offset');
			}
			
			// prepare data
			data = {"params":{
					"conditions":[cond],
					"order":examSortBy+" "+examSortOrder,
					"limit": offset ? 3 : 100000,
					"offset":offset
					}
				};
			
			
			// send ajax request	
			ajax(
				baseURL + 'core/ajax/'+role+'/exam/read',
				data,
				function (examData) {
					if(examData.length) {
						populateExamGrid(examData, section, cond.length);
					} else {
						alert("No such exam found!");
					}
				}
			);
		};
		
		var populateExamGrid = function (examData, section, populateInSearchGrid) {
			
			var populatePages = false;
			if(populateInSearchGrid) {
				if($(section).find('.exam-listing-area').length > 1) {
					var examListingArea = $(section).find('.exam-listing-area').first();
				} else {
					var examListingArea = $(section).find('.exam-listing-area').clone();
					populatePages = true;
				}
			} else {
				var examListingArea = $(section).find('.exam-listing-area');				
			}
			$(examListingArea).find('.exam-list').find('li').not(':first').not(':first').remove();
			
			$.each(examData, function(i, exam) {
				start = exam.starttime ? exam.starttime.split(" ") : ["0000:00:00", "00:00"];
				ends  = exam.endtime ? exam.endtime.split(" ") : ["0000:00:00", "00:00"];
				
				var examItem = $(examListingArea).find('.exam-list').find('li').not(':first').first().clone();
				
				$(examItem).find('.exam-name').html(exam.name);
				$(examItem).find('.exam-descr').html(exam.descr.replace(/(<([^>]+)>)/ig, "").substring(0, 120)+" ...");
				$(examItem).find('.exam-date').html(start[0]);
				$(examItem).find('.exam-starts').html(start[1]);
				$(examItem).find('.exam-ends').html(ends[1]);				
				
				$(examListingArea).find('.exam-list').append(examItem);
			
				if(i == '2') {
					return false;
				} 
			});
			
			if(populatePages) {
				var totalPgs = Math.ceil(examData.length/3);
				var pagination = $(examListingArea).find('.pagination');
				$(pagination).find('li').not(':first').remove();
				for(i=1; i<=totalPgs; i++) {
					pageItem = $(pagination).find('li').first().removeClass('active').clone();
					$(pageItem).find('a').html(i);
					$(pageItem).attr('offset', ((i-1) * 3));
					if(i == 1) {
						$(pageItem).addClass('active');
					}
					$(pagination).append(pageItem);
				}
				$(pagination).find('li').first().remove();
			}
			
			$(examListingArea).find('.exam-list').find('li').eq(1).remove();
			$(section).find('.exam-listing-area').parent().prepend(examListingArea);
		};
		
	});
	
