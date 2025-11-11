<div class="wrap nxt-importer-wrap">
	<h1>CPT Media Importer</h1>
	
	<div class="nxt-importer-card">
		<h2>Import Media Files to Custom Post Type</h2>
		<p>Upload images that will be imported as posts with featured images. The filename will be used to generate the post title and alt text.</p>
		
		<form id="nxt-importer-form" method="post" enctype="multipart/form-data">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="post_type">Target Post Type</label>
					</th>
					<td>
						<select name="post_type" id="post_type" required>
							<option value="">Select Post Type...</option>
							<?php foreach ($post_types as $pt) : ?>
								<option value="<?php echo esc_attr($pt->name); ?>" <?php selected($pt->name, 'kundenlogo'); ?>>
									<?php echo esc_html($pt->label); ?> (<?php echo esc_html($pt->name); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">Select the custom post type where the posts should be created.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="files">Media Files</label>
					</th>
					<td>
						<div class="nxt-upload-area" id="nxt-upload-area">
							<input type="file" name="files[]" id="files" multiple accept="image/*" required>
							<div class="nxt-upload-placeholder">
								<span class="dashicons dashicons-upload"></span>
								<p><strong>Click to select files</strong> or drag and drop here</p>
								<p class="description">Supported formats: JPG, PNG, GIF, SVG, WebP</p>
							</div>
							<div class="nxt-upload-preview" id="nxt-upload-preview"></div>
						</div>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<button type="submit" class="button button-primary button-hero" id="nxt-import-btn">
					<span class="dashicons dashicons-download"></span> Start Import
				</button>
			</p>
		</form>
		
		<div id="nxt-import-progress" class="nxt-progress" style="display: none;">
			<h3>Import Progress</h3>
			<div class="nxt-progress-bar">
				<div class="nxt-progress-fill" id="nxt-progress-fill"></div>
			</div>
			<p class="nxt-progress-text" id="nxt-progress-text">Processing...</p>
		</div>
		
		<div id="nxt-import-results" class="nxt-results" style="display: none;">
			<h3>Import Results</h3>
			<div id="nxt-results-content"></div>
		</div>
	</div>
	
	<div class="nxt-importer-card nxt-help">
		<h2>How to Use</h2>
		<h3>Option 1: Admin Interface (Current Page)</h3>
		<ol>
			<li>Select your target post type (e.g., "kundenlogo")</li>
			<li>Upload your media files (drag & drop or click to select)</li>
			<li>Click "Start Import"</li>
		</ol>
		
		<h3>Option 2: WP-CLI Command</h3>
		<p>For bulk imports from a local directory, use the command line:</p>
		<pre><code>wp nxt import-media --directory=/path/to/logos --post-type=kundenlogo</code></pre>
		<p><strong>Example:</strong></p>
		<pre><code>wp nxt import-media --directory=/Users/yourname/Desktop/kundenlogos --post-type=kundenlogo</code></pre>
		
		<h3>Filename Convention</h3>
		<p>The filename will be automatically converted to a readable format:</p>
		<ul>
			<li><code>Audi-Logo.svg</code> → Post Title: "Audi" | Alt Text: "Audi"</li>
			<li><code>BMW_corporate-logo.png</code> → Post Title: "Bmw Corporate" | Alt Text: "Bmw Corporate"</li>
			<li><code>microsoft-logo-2024.jpg</code> → Post Title: "Microsoft 2024" | Alt Text: "Microsoft 2024"</li>
		</ul>
		<p><strong>Note:</strong> The word "Logo" is automatically removed from titles and alt texts.</p>
		
		<h3>Notes</h3>
		<ul>
			<li>Duplicate posts (same title) will be skipped automatically</li>
			<li>Each file becomes a new post with the image as featured image</li>
			<li>Alt text is automatically generated from the filename (without "Logo")</li>
		</ul>
	</div>
</div>

