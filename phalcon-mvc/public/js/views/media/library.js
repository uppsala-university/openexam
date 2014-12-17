// JavaScript Document specific to Exam create
// @Author Ahsan Shahzad [MedfarmDoIT]


		
	/*----------------------------------------------*/ 
	/*	Media library related event bindings		*/
	/*----------------------------------------------*/
		
	$(function () {
		'use strict';
		
		// initialize tabs
		$('#media_types').tabs();
		
		var url = baseURL + 'utility/media/upload';
		
		$('#fileupload').fileupload({
			url: url,
			dataType: 'json',
			done: function (e, data) {
				$('#lib-default-msg').hide();
				
				$.each(data.result.files, function (index, file) {

					if(typeof file.url != 'undefined') {
						
						// add this image in selected library image area
						var thumbnail = ((file.type.indexOf('image') < 0) ? baseURL + "img/file-icon.png" : file.url);
						var newItem = $( "#selected-lib-img >li:first" ).clone()
						newItem.attr('media-id', '')
							.find('img')
								.attr('src', thumbnail)
							.end()
							.find('.title-box')
								.attr('file-path', file.url)
							.end()	
							.show();
						
						$( "#selected-lib-img" ).prepend(newItem);
						$( "#selected-lib-img" ).sortable();
						
						if(confirm("File '"+file.name+"' has been uploaded successfully. \n\r Do you want to save this file in your file library for using it in future as well? "))
						{
							var fType = file.type.split('/');
							// send ajax request to insert this upload in resource table
							ajax (
								baseURL + 'core/ajax/contributor/resource/create',
								{"exam_id":examId, "name":file.name, "path":file.url, "type":fType[0], "subtype":fType[1], "user":user},
								function (rData) {
									fType[0] = fType[0]+'s';
									var tabId = ($('#'+fType[0]+'-tab').length) ? '#'+fType[0]+'-tab' : '#other-files-tab';
									
									// show this image in library on right side
									var newItem = $(tabId).find(".recent-uploads").find('.lib-item:hidden').clone();
									newItem .attr('id', 'lib-item-'+rData.id)
										.find('.selected-lib-img')
											.find('img')
												.attr('src', file.url)
												.attr('file-path', file.url)
											.end()	
										.end()
										.find('.lib-img')
											.attr('src', (fType[0] == 'images' ? file.url : baseURL + "img/file-icon.png"))
											.attr('file-path', file.url)
										.end()
										.find('.lib-item-title')
											.html(file.name)
										.end()
										.find('.lib-item-settings > i')
											.attr('item-title', file.name)
											.attr('media-id', rData.id)
										.end()
										.show();
									$(tabId).find(".recent-uploads").append(newItem);
									
									// update media id of image previously added on left side
									$('#lib-items-added')
										.find('.title-box[file-path="'+file.url+'"]')
										.closest('li')
										.attr('media-id', 'lib-item-'+rData.id);
									
									refreshSettingsTooltips();
									
								}
							);
						}
						
						
					} else {
						alert("Unable to upload file '"+ file.name + "': " + file.error)
					}
				});
			}
		}).prop('disabled', !$.support.fileInput)
			.parent().addClass($.support.fileInput ? undefined : 'disabled');
			

		// attach settings tooltip on all library items
		var refreshSettingsTooltips = function() {
			$(".lib-item-settings > i").each(function(i, tagElement) {
				
				var title 	= $(tagElement).attr('item-title');
				var shared 	= $(tagElement).attr('item-share');
				var mediaId = $(tagElement).attr('media-id');
				
				new Opentip(tagElement,
						'<div>\
							<div>Title</div>\
							<div><input type="text" class="update-lib-item-title" value="'+title+'"></div>\
							\
							<br style="clear:both">\
							\
							<div>This can be reused </div>\
							<div>\
								<select class="update-lib-item-shared" style="width:145px; height:25px">\
									<option value="exam" '+(shared == 'exam' ? 'selected' :'')+'>only for my exams</option>\
									<option value="group" '+(shared == 'group' ? 'selected' :'')+'>for all exams in my department</option>\
									<option value="global" '+(shared == 'global' ? 'selected' :'')+'>for any exam within Uppsala University</option>\
								</select>\
							</div>\
							<br />\
							<span class="btn btn-success update-lib-item-details" media-id="'+mediaId+'"> Save</span>\
							<span class="btn btn-danger del-lib-item-details" media-id="'+mediaId+'"> Delete it!</span>\
						</div>',
						{style: "drops", tipJoint: "top left"});
			});
			
		} 
		refreshSettingsTooltips();
			
		if(libJs == 'loaded') {
			return;
		} else {
			libJs = 'loaded';
		}
			
			
		$(document).on('click', '.select-lib-img', function() {
			if($(this).hasClass('select-lib-img')) {
				var mediaId = $(this).parent().attr('id');
				var title = $(this).parent().find('.lib-item-title').html();
				
				$('#lib-default-msg').hide();
				
				var newItem = $( "#selected-lib-img >li:first" ).clone()
				newItem.attr('media-id', mediaId)
					.find('img')
						.attr('src', $(this).find('img').attr('src'))
					.end()
					.find('.title-box')
						.attr('file-path', $(this).find('img').attr('file-path'))
						.find('input')
							.val(title)
						.end()
					.end()
					.show();
				
				$('#lib-items-added > ul').append(newItem);
				$('#lib-items-added > ul > li:last').find('input').focus().select();
				
				$(this).find('span').html('<i class="fa fa-check-square-o"></i> Selected');
				$(this).removeClass('select-lib-img').addClass('selected-lib-img').removeAttr('href');
				$( "#selected-lib-img" ).sortable();
			}
			return false;
		});
		
		$( "#selected-lib-img" ).sortable();
		
		/** update title and share settings **/
		$(document).on('click', '.update-lib-item-details', function() {
			// grab data
			var title 	= $(this).parent().find('.update-lib-item-title').val();
			var shared 	= $(this).parent().find('.update-lib-item-shared').val();
			var mediaId 	= $(this).attr('media-id');
			
			// send ajax request to save data
			ajax (
				baseURL + 'core/ajax/contributor/resource/update',
				{"id":mediaId, "name":title, "shared":shared},
				function (rData) {
					// update dom as per the changes
					$('#lib-item-'+mediaId).find('.lib-item-title').html(title);
					$('#lib-items-added > ul').find('li[media-id="lib-item-'+mediaId+'"] > .title-box').find('input').val(title);
					// close tooltip
					close_tooltips();
				}
			);
			
		});

		$(document).on('click', '.del-lib-item-details', function() {
			// grab data
			var mediaId 	= $(this).attr('media-id');
			
			if(confirm('Are you sure you want to delete this library file?')) {
				// send ajax request to save data
				ajax (
					baseURL + 'core/ajax/contributor/resource/delete',
					{"id":mediaId},
					function (rData) {
						// update dom as per the changes
						$('#lib-item-'+mediaId).remove();
						$('#lib-items-added > ul').find('li[media-id="lib-item-'+mediaId+'"]').remove();
						// close tooltip
						close_tooltips();
					}
				);
			}
		});
		
		
		$(document).on('click', '#selected-lib-img li i', function() {
			$('#'+$(this).parent().attr('media-id'))
				.find('.selected-lib-img')
					.removeClass('selected-lib-img')
					.addClass('select-lib-img')
					.find('span')
						.html('Click to select')
					.end();
			$(this).parent().hide(300).remove();
			if(!$('#lib-items-added > ul > li').length)
				$('#lib-default-msg').show(500);
			
		});

		
	});
			
