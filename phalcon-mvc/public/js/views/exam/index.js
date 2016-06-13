// JavaScript Document specific to exam/index
// @Author Ahsan Shahzad [MedfarmDoIT]

/*-- var initialization --*/	
var stEvents = '';
	
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
		$("#reuse-exam-dialog").dialog({
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
								location.href = baseURL + 'exam/update/' + resp.exam_id + '/creator';
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
		
		if(confirm("Are you sure you want to delete this Exam: '"+jQuery.trim(examName)+"'")) {
			
			ajax(
				baseURL + 'core/ajax/creator/exam/delete',
				{"id": examId},
				function (examData) {
					$(examLine).slideUp(500, function() {
						$(this).remove();
						location.reload();
					});
				},
				"POST",
				true,
				false
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

	$(document).on('click', '.change-time', function() {
		var examListWrapper = $(this).closest('.list-group-item');
		var examId = $(examListWrapper).attr('data-id');
		
		//pick data and populate in dialog
		$('#change-time')
			.find('#exam-title').html($(examListWrapper).find('.exam-name').html()).end()
			.find('#exam-date').val($(examListWrapper).find('.exam-date > span').html()).end()
			.find('#exam-starttime').val($(examListWrapper).find('.exam-starts').html()).end()
			.find('#exam-endtime').val($(examListWrapper).find('.exam-ends').html()).end();
		
		$("#change-time").dialog({
			autoOpen: true,
			modal: true,
			buttons: {
				"Update date and time »": function() {
					
					dialog = $(this);
					
					// prepare data
					starttime = $('#exam-date').val()+" "+$('#exam-starttime').val();
					endtime =  $('#exam-date').val()+" "+$('#exam-endtime').val(); 
					
					// update in db
					ajax(
						baseURL + 'core/ajax/invigilator/exam/update', 
						{"id":examId, "starttime":starttime , "endtime":endtime}, 
						function(status) {
							
							//update in grid
							$(examListWrapper)
								.find('.exam-date > span').html($('#exam-date').val()).end()
								.find('.exam-starts').html($('#exam-starttime').val()).end()
								.find('.exam-ends').html($('#exam-endtime').val()).end();																
								
							$(dialog).dialog('destroy');
						}
					);
/*					
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
					});*/
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


	var reloadExamList = function (element, offset) {

		// section
		var section = $(element).closest('.exam-listing-wrapper');
		var role = $(section).attr('exam-role');
		var examSortBy = $(section).find('.exam-sort-by').val();
		var examSortOrder = $(section).find('.exam-sort-order').attr('order');
		var searchKey = $(section).find('.exam-search-box').val();
		if(searchKey) {
			var cond = ["name like :key: or code like :key:", {"key":"%"+searchKey+"%"}];
		} else {
			var cond = [];
		}
		
		if(typeof offset === 'undefined') {
			offset = $(section).find('.pagination > .active').attr('offset');
		}
		//"flags":["upcoming"]
		// prepare data
		data = {"params":{
				"role":role,
				"conditions":[cond],
				"order":examSortBy+" "+examSortOrder,
				"limit": offset ? examPerPage : 100000,
				"offset":offset
				}
			};
		
		
		// send ajax request	
		ajax(
			baseURL + 'core/ajax/'+role+'/exam/read',
			data,
			function (examData) {
				if(examData.length) {
					//alert(JSON.stringify(examData));
					populateExamGrid(examData, section, cond.length);
				} else {
					alert("No such exam found!");
				}
			}
		);
	};
	
	var populateExamGrid = function (examData, section, populateInSearchGrid) {
		
		var populatePages = false;
		var examRole = $(section).attr('exam-role')!=$(section).attr('section-role')?$(section).attr('section-role'):$(section).attr('exam-role');
		
		// grid that appears when someone searches for exam
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
			examName = exam.name == ''||exam.name == ' '?'Untitled exam':exam.name;
			var examItem = $(examListingArea).find('.exam-list').find('li').not(':first').first().clone();
			$(examItem).attr('data-id', exam.id);
			$(examItem).find('.exam-name').html(examName + (exam.code!=''&&exam.code!=null ? " ("+exam.code+")" : ""));
			/*$(examItem).find('.exam-descr').html(exam.descr.replace(/(<([^>]+)>)/ig, "").substring(0, 120)+" ...");*/
			if(exam.starttime) {
				$(examItem).find('.exam-date-time').show();
				$(examItem).find('.exam-date').html(start[0]);
				$(examItem).find('.exam-starts').html(start[1]);
				$(examItem).find('.exam-ends').html(ends[1]);				
			} else {
				$(examItem).find('.exam-date-time').hide();
			}
			if(exam.published) {
				$(examItem).find('.published-exam').show();
				$(examItem).find('.draft-exam').hide();
				if(examRole == 'creator') {
					$(examItem).css('background-color','#FEFFD5');
				}
			} else {
				$(examItem).find('.published-exam').hide();
				$(examItem).find('.draft-exam').show();
				$(examItem).css('background-color','#fff');
			}
				
			//list operational buttons as per the exam role and status
			$(examItem).find('.exam-show-options').empty();
			$.each(examSections[examRole]["show-options"], function(btnKey, btnProp) {
				
				var showBtn = false;
				if(btnProp["show-on-flags"] == '*') {
					showBtn = true;
				} else {
					$.each(btnProp["show-on-flags"], function(i, flag) {
						if(exam.flags.indexOf(flag) >= 0) {
							showBtn = true;
							return false;
						}
					});
				}
				
				if(showBtn) {
					target = btnProp["target"].indexOf('/') >= 0 ? baseURL + (btnProp["target"].replace("{exam-id}", exam.id)) : '#';
					btnClass = btnProp["target"].indexOf('/') >= 0 ? "" : btnProp["target"]+" prevent";
					$(examItem)
						.find('.exam-show-options')
						.append('<a class="'+btnClass+'" href="'+target+'" data-id="'+exam.id+'">'+$('#'+btnKey).html()+'</a>');
				}
			})
			
			
			$(examListingArea).find('.exam-list').append(examItem);
		
			if(i == examPerPage-1) {
				return false;
			} 
		});
		
		if(populatePages) {
			var totalPgs = Math.ceil(examData.length/examPerPage);
			var pagination = $(examListingArea).find('.pagination');
			$(pagination).find('li').not(':first').remove();
			for(i=1; i<=totalPgs; i++) {
				pageItem = $(pagination).find('li').first().removeClass('active').clone();
				$(pageItem).find('a').html(i);
				$(pageItem).attr('offset', ((i-1) * examPerPage));
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

