(function() {
	'use strict';
	
	document.addEventListener('DOMContentLoaded', function() {
		const form = document.getElementById('nxt-importer-form');
		const fileInput = document.getElementById('files');
		const uploadArea = document.getElementById('nxt-upload-area');
		const preview = document.getElementById('nxt-upload-preview');
		const progressDiv = document.getElementById('nxt-import-progress');
		const progressFill = document.getElementById('nxt-progress-fill');
		const progressText = document.getElementById('nxt-progress-text');
		const resultsDiv = document.getElementById('nxt-import-results');
		const resultsContent = document.getElementById('nxt-results-content');
		
		if (!form) return;
		
		fileInput.addEventListener('change', handleFileSelect);
		
		uploadArea.addEventListener('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			uploadArea.classList.add('dragover');
		});
		
		uploadArea.addEventListener('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();
			uploadArea.classList.remove('dragover');
		});
		
		uploadArea.addEventListener('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			uploadArea.classList.remove('dragover');
			
			const files = e.dataTransfer.files;
			if (files.length > 0) {
				fileInput.files = files;
				handleFileSelect({ target: { files: files } });
			}
		});
		
		form.addEventListener('submit', handleSubmit);
		
		function handleFileSelect(e) {
			const files = e.target.files;
			if (!files.length) return;
			
			preview.innerHTML = '';
			uploadArea.classList.add('has-files');
			
			Array.from(files).forEach(function(file) {
				const fileItem = document.createElement('div');
				fileItem.className = 'file-preview-item';
				
				const icon = getFileIcon(file.type);
				const fileName = document.createElement('span');
				fileName.textContent = file.name;
				
				fileItem.innerHTML = '<span class="dashicons ' + icon + '"></span> ';
				fileItem.appendChild(fileName);
				
				preview.appendChild(fileItem);
			});
		}
		
		function getFileIcon(mimeType) {
			if (mimeType.startsWith('image/')) {
				return 'dashicons-format-image';
			}
			return 'dashicons-media-default';
		}
		
		function handleSubmit(e) {
			e.preventDefault();
			
			const postType = document.getElementById('post_type').value;
			const files = fileInput.files;
			
			if (!postType) {
				alert('Please select a post type');
				return;
			}
			
			if (!files.length) {
				alert('Please select at least one file');
				return;
			}
			
			const formData = new FormData();
			formData.append('action', 'nxt_import_media_batch');
			formData.append('nonce', nxtImporter.nonce);
			formData.append('post_type', postType);
			
			for (let i = 0; i < files.length; i++) {
				formData.append('files[' + i + ']', files[i]);
			}
			
			progressDiv.style.display = 'block';
			resultsDiv.style.display = 'none';
			progressFill.style.width = '0%';
			progressText.textContent = nxtImporter.strings.uploading;
			
			form.querySelector('.submit').style.opacity = '0.5';
			form.querySelector('.submit').style.pointerEvents = 'none';
			
			fetch(nxtImporter.ajax_url, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.success) {
					showResults(data.data);
				} else {
					alert(nxtImporter.strings.error + ': ' + (data.data?.message ?? 'Unknown error'));
				}
			})
			.catch(function(error) {
				alert(nxtImporter.strings.error + ': ' + error.message);
			})
			.finally(function() {
				form.querySelector('.submit').style.opacity = '1';
				form.querySelector('.submit').style.pointerEvents = 'auto';
				progressDiv.style.display = 'none';
			});
		}
		
		function showResults(data) {
			resultsDiv.style.display = 'block';
			
			const results = data.results ?? [];
			const total = data.total ?? 0;
			const successful = data.successful ?? 0;
			const failed = total - successful;
			
			let html = '<div class="nxt-results-summary">';
			html += '<p><strong>Total:</strong> ' + total + ' files</p>';
			html += '<p><strong>Successful:</strong> <span class="success">' + successful + '</span></p>';
			if (failed > 0) {
				html += '<p><strong>Failed:</strong> <span class="error">' + failed + '</span></p>';
			}
			html += '</div>';
			
			html += '<table class="widefat striped">';
			html += '<thead><tr>';
			html += '<th>Status</th>';
			html += '<th>Filename</th>';
			html += '<th>Details</th>';
			html += '</tr></thead>';
			html += '<tbody>';
			
			results.forEach(function(result) {
				html += '<tr class="' + (result.success ? 'success-row' : 'error-row') + '">';
				html += '<td>';
				if (result.success) {
					html += '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>';
				} else {
					html += '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>';
				}
				html += '</td>';
				html += '<td><code>' + escapeHtml(result.filename) + '</code></td>';
				html += '<td>';
				if (result.success) {
					html += 'Post ID: <a href="post.php?post=' + result.post_id + '&action=edit" target="_blank">' + result.post_id + '</a><br>';
					html += 'Title: ' + escapeHtml(result.post_title);
				} else {
					html += '<span style="color: #dc3232;">' + escapeHtml(result.message) + '</span>';
				}
				html += '</td>';
				html += '</tr>';
			});
			
			html += '</tbody></table>';
			
			resultsContent.innerHTML = html;
			
			form.reset();
			preview.innerHTML = '';
			uploadArea.classList.remove('has-files');
		}
		
		function escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	});
})();

