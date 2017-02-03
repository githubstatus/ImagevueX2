if (!Imagevue)
	var Imagevue = {};

if (!Imagevue.Environment)
	Imagevue.Environment = {};

Imagevue.Environment.resizeQuality = 95;
Imagevue.Environment.imageExtensions = ['gif', 'jpeg', 'jpg', 'tif', 'tiff', 'png'];

(function ($) {
	Imagevue.UploadProgress = function(selector) {
		this.init(selector);
	}

	$.extend(Imagevue.UploadProgress.prototype, {
		init: function(selector) {
			this.element = $(selector);
			this.statuses = {};
		},

		addFile: function(file) {
			var div = $('<div></div>');
			div.html(file.name + ' &nbsp; ');
			this.element.append(div);
			this.statuses[file.id] = div;
			this.setStatus(Imagevue.UploadProgress.PENDING, file, '');
		},

		setStatus: function(statusType, file, message) {
			if (!this.statuses[file.id]) {
				this.addFile(file);
			}
			var div = this.statuses[file.id];
			var span = $(div).find('span')[0];
			if (!span)
				span = $('<span></span>').appendTo($(div));

			var imageUrl;
			switch (statusType) {
				case Imagevue.UploadProgress.NOTICE:
					imageUrl = 'assets/images/icon_checked.gif';
					break;
				case Imagevue.UploadProgress.WARNING:
					imageUrl = 'assets/images/icon_warning.gif';
					break;
				case Imagevue.UploadProgress.ERROR:
					imageUrl = 'assets/images/icon_delete.gif';
					break;
				case Imagevue.UploadProgress.PROGRESS:
					imageUrl = 'assets/images/icon_upload.gif';
					break;
				case Imagevue.UploadProgress.PENDING:
					imageUrl = 'assets/images/icon_checkbox.gif';
					break;
				default:
					return;
					break;
			}

			$(span).html('<img src="' + imageUrl + '" alt="" /> <span>' + message + '</span>');
		}
	});

	$.extend(Imagevue.UploadProgress, {
		NOTICE: 'notice',
		WARNING: 'warning',
		ERROR: 'error',
		PROGRESS: 'progress'
	});

	$(document).ready(function () {
		if (!$('#uploaderContainer') && !$('#htmlUploaderContainer'))
			return;

		var up = new Imagevue.UploadProgress('#fsUploadProgress');

		switch (Imagevue.Environment.uploaderType) {
			case 'swfupload':
				Imagevue.initUpload = function () {
					var postParams = {};
					postParams[Imagevue.Environment.sessionName] = Imagevue.Environment.sessionId;

					var settings = {
						flash_url: 'swfupload.swf',
						upload_url: 'index.php?a=upload&path=' + Imagevue.Environment.currentUrlencodedPath,  // Relative to the SWF file
						post_params: postParams,
						file_size_limit: Imagevue.Environment.uploadMaxFilesize + 'B',
						file_types: $.map(Imagevue.Environment.allowedExtensions, function (ext) {return '*.' + ext;}).join(';'),
						file_types_description: 'All Files',
						file_upload_limit: 0,
						file_queue_limit: 0,
						debug: true,
						debug_handler: SWFUpload.Console.writeLine,

						// Button settings
						button_image_url: 'assets/images/uploadFileButton.png',  // Relative to the Flash file
						button_width: '100',
						button_height: '40',
						button_placeholder_id: 'uploaderContainer',

						file_queued_handler: function(file) {
							up.addFile(file);
							if (!this.imagevueQueue)
								this.imagevueQueue = new Array();
							this.imagevueQueue.push(file.id);
						},

						file_queue_error_handler: function(file, errorCode, message) {
							switch (errorCode) {
								case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'File is too big');
									break;
								case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Cannot upload Zero Byte files');
									break;
								case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Invalid File Type');
									break;
								default:
									if (file !== null)
										up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Unhandled Error');
									break;
							}
						},

						file_dialog_complete_handler: function (numFilesSelected, numFilesQueued) {
							if (numFilesQueued > 0) {
								var self = this;
								setTimeout(function () {
									var nextFileId;
									while (!nextFileId || !self.getFile(nextFileId)) {
										if (self.imagevueQueue.length < 1)
											return;
										nextFileId = self.imagevueQueue.shift();
									}

									self.addPostParam('overwrite', $('#uploadOverwriteCheckbox').prop('checked') ? 1 : 0);

									var extension = self.getFile(nextFileId).name.substr(self.getFile(nextFileId).name.lastIndexOf('.') + 1).toLowerCase();

									if (('0' != $('#uploadResizeOnBackend').val()) && $('#uploadResizeCheckbox').prop('checked')) {
										self.setUploadURL('index.php?a=upload&resize=1&width=' + $('#uploadResizeWidth').val() + '&height=' + $('#uploadResizeHeight').val() + '&path=' + Imagevue.Environment.currentUrlencodedPath);
										self.startUpload(nextFileId);
									} else {
										self.setUploadURL('index.php?a=upload&resize=0&path=' + Imagevue.Environment.currentUrlencodedPath);
										if ($('#uploadResizeCheckbox').prop('checked') && ($.inArray(extension, Imagevue.Environment.imageExtensions) >= 0))
										{
											self.startResizedUpload(nextFileId, $('#uploadResizeWidth').val(), $('#uploadResizeHeight').val(), SWFUpload.RESIZE_ENCODING.JPEG, Imagevue.Environment.resizeQuality, false);
										}
										else
										{
											self.startUpload(nextFileId);
										}
									}
								}, 100);
							}
						},

						upload_start_handler: function (file) {
							up.setStatus(Imagevue.UploadProgress.PROGRESS, file, 'Uploading...');
							return true;
						},

						upload_progress_handler: function (file, bytesLoaded, bytesTotal) {
							var percent = bytesTotal ? Math.ceil((bytesLoaded / bytesTotal) * 100) : 0;
							up.setStatus(Imagevue.UploadProgress.PROGRESS, file, 'Uploading (' + percent + '%)...');
						},

						upload_error_handler: function (file, errorCode, message) {
							switch (errorCode) {
								case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Upload Error: ' + message);
									break;
								case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Upload Failed');
									break;
								case SWFUpload.UPLOAD_ERROR.IO_ERROR:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Server (IO) Error');
									break;
								case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Security Error');
									break;
								case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Upload limit exceeded');
									break;
								case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Failed Validation. Upload skipped');
									break;
								case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Cancelled');
									break;
								case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
									up.setStatus(Imagevue.UploadProgress.WARNING, file, 'Stopped');
									break;
								case SWFUpload.UPLOAD_ERROR.RESIZE:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'File cannot be resized');
									break;
								default:
									up.setStatus(Imagevue.UploadProgress.ERROR, file, 'Unhandled Error: ' + errorCode);
									break;
							}
						},

						upload_success_handler: function (file, serverData) {
							up.setStatus(Imagevue.UploadProgress.NOTICE, file, 'Uploaded');
						},

						upload_complete_handler: function (file) {
							if (this.getStats().files_queued > 0) {
								do {
									if (this.imagevueQueue.length < 1)
										return;
									var nextFileId = this.imagevueQueue.shift();
								} while (nextFileId == file.id || !this.getFile(nextFileId));
								var extension = this.getFile(nextFileId).name.substr(this.getFile(nextFileId).name.lastIndexOf('.') + 1).toLowerCase();
								if ('0' != $('#uploadResizeOnBackend').val() && $('#uploadResizeCheckbox').prop('checked')) {
									this.setUploadURL('index.php?a=upload&resize=1&width=' + $('#uploadResizeWidth').val() + '&height=' + $('#uploadResizeHeight').val() + '&path=' + Imagevue.Environment.currentUrlencodedPath);
									this.startUpload(nextFileId);
								} else {
									this.setUploadURL('index.php?a=upload&resize=0&path=' + Imagevue.Environment.currentUrlencodedPath);
									if ($('#uploadResizeCheckbox').prop('checked') && ($.inArray(extension, Imagevue.Environment.imageExtensions) >= 0)) {
										this.startResizedUpload(nextFileId, $('#uploadResizeWidth').val(), $('#uploadResizeHeight').val(), SWFUpload.RESIZE_ENCODING.JPEG, Imagevue.Environment.resizeQuality, false);
									} else {
										this.startUpload(nextFileId);
									}
								}
							} else {
								var loc = (window.location.href.substr(0, (window.location.href.indexOf('#') > 0) ? window.location.href.indexOf('#') : window.location.href.length));
								window.location = loc;
							}
						},

						preserve_relative_urls: true,
						prevent_swf_caching: false
					};

					Imagevue.SWFUpload = new SWFUpload(settings);
				}

				Imagevue.destroyUpload = function () {
					$('<div id="uploaderContainer" style="height: 50px; width: 100px;">&nbsp;</div>').appendTo($(Imagevue.SWFUpload.getMovieElement().parentNode));
					Imagevue.SWFUpload.destroy();
					delete Imagevue.SWFUpload;
				};
				break;
			default:
				Imagevue.initUpload = function () {}
				Imagevue.destroyUpload = function () {}
				function submitUpload() {
					var forms = $('#htmlUploaderContainer').find('form').filter(function () {return !$('input[type=file]', this).filter(function () {return !$(this).val();}).size()});
					if (forms.length) {
						var form = $(forms.first());

						if ($('#uploadOverwriteCheckbox').prop('checked'))
							form.attr('action', form.attr('action') + '&overwrite=1');

						if ($('#uploadResizeCheckbox').prop('checked'))
							form.attr('action', form.attr('action') + '&resize=1&width=' + $('#uploadResizeWidth').val() + '&height=' + $('#uploadResizeHeight').val());

						up.setStatus(Imagevue.UploadProgress.PROGRESS, {id: form.attr('id')}, 'Uploading...');
						$('#myIframe').load({up: up}, function(event) {
						event.data.up.setStatus(Imagevue.UploadProgress.NOTICE, {id: form.attr('id')}, 'Uploaded');
							form.detach();
							setTimeout(submitUpload, 0);
						});
						form.submit();
					} else {
						var loc = (window.location.href.substr(0, (window.location.href.indexOf('#') > 0) ? window.location.href.indexOf('#') : window.location.href.length));
						window.location = loc;
					}
				}

				var id = 0;

				function reset() {
					var form = $('#htmlUploader');
					if (form && !form.find('input[type=file]').filter(function () {return !$(this).val();}).size()) {
						var fileName = $('input[type=file]', form).first().val();
						fileName = fileName.substr(Math.max(fileName.lastIndexOf('/'), fileName.lastIndexOf('\\')) + 1);
						up.addFile({id: 'upload_' + id, name: fileName});
						var newForm = $(form.clone());
						form.attr('id', 'upload_' + id).hide();
						newForm.attr('id', 'htmlUploader');
						$(newForm.find('input[type=file]').first()).replaceWith('<input type="file" name="Filedata" />');
						$(newForm.find('input[type=file]').first()).change(reset);
						$('#htmlUploaderContainer').append(newForm);
						id++;
					}
				}

				if ($('#htmlUploader')) {
					$('input[type=file]', '#htmlUploader').change(reset);
					$('#htmlUploaderSubmitButton').click(function () {
						$(this).attr('disabled', true);
						submitUpload();
					});
				}

				break;
		}
	});
})(jQuery);