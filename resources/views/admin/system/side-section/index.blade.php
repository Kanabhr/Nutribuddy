@extends('layouts.admin')
@php
	$title = 'Side Section Settings';
	$subTitle = 'Settings / Side Section';
	$sideSectionLogo = $settings['side_section_logo'] ?? null;
	$hasSideSectionLogo = $sideSectionLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($sideSectionLogo);
@endphp

@section('content')
	@if(session('success'))
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			{{ session('success') }}
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	@endif

	@if($errors->any())
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<strong>Error!</strong> Please check the following issues:
			<ul class="mb-0 mt-2">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	@endif

	<form action="{{ route('admin.ecommerce.side-section.update') }}" method="POST" enctype="multipart/form-data">
		@csrf
		<div class="row gy-4">
			<!-- Main Content: General Details -->
			<div class="col-lg-8">
				<div class="card h-100">
					<div class="card-header">
						<h5 class="card-title mb-0">General Information</h5>
					</div>
					<div class="card-body">
						<div class="row g-4">
							<!-- Logo Upload -->
							<div class="col-12">
								<label class="form-label">Logo Image</label>
								<div class="d-flex align-items-start gap-4">
									<div class="avatar-preview"
										style="width: 120px; height: 120px; border: 2px dashed #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; overflow: hidden;"
										id="imagePreviewContainer">
										@if($hasSideSectionLogo)
											<img id="logoPreviewImg" src="{{ asset('storage/' . $sideSectionLogo) . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($sideSectionLogo) }}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
											<div id="imagePlaceholder" class="text-center d-flex flex-column align-items-center text-secondary-light" style="display: none;">
												<iconify-icon icon="lucide:image" width="32" height="32" class="mb-1"></iconify-icon>
												<span style="font-size: 12px;">No Logo</span>
											</div>
										@else
											<div id="imagePlaceholder"
												class="text-center d-flex flex-column align-items-center text-secondary-light">
												<iconify-icon icon="lucide:image" width="32" height="32"
													class="mb-1"></iconify-icon>
												<span style="font-size: 12px;">No Logo</span>
											</div>
										@endif
									</div>
									<div class="avatar-edit d-flex flex-column gap-2">
										<input type='file' id="imageUpload" name="logo"
											accept=".png, .jpg, .jpeg, .svg, .webp" class="d-none" />
										<label for="imageUpload"
											class="btn btn-primary-600 btn-sm radius-8 d-inline-flex align-items-center gap-2">
											<iconify-icon icon="lucide:upload"></iconify-icon> Upload Logo
										</label>
										<small class="text-secondary-light">Allowed formats: JPEG, PNG, SVG. Max size:
											2MB.</small>
									</div>
								</div>
							</div>

							<!-- Contact Number -->
							<div class="col-md-6">
								<label class="form-label">Contact Number <span class="text-danger">*</span></label>
								<div class="icon-field">
									<span class="icon">
										<iconify-icon icon="lucide:phone"></iconify-icon>
									</span>
									<input type="text" name="contact_number" class="form-control"
										placeholder="e.g. +1 234 567 8900" value="{{ $settings['side_section_contact_number'] ?? '' }}" required>
								</div>
							</div>

							<!-- Email -->
							<div class="col-md-6">
								<label class="form-label">Email Address <span class="text-danger">*</span></label>
								<div class="icon-field">
									<span class="icon">
										<iconify-icon icon="lucide:mail"></iconify-icon>
									</span>
									<input type="email" name="email" class="form-control"
										placeholder="e.g. contact@example.com" value="{{ $settings['side_section_email'] ?? '' }}" required>
								</div>
							</div>

							<!-- Address -->
							<div class="col-12">
								<label class="form-label">Physical Address</label>
								<div class="icon-field">
									<span class="icon align-items-start mt-2">
										<iconify-icon icon="lucide:map-pin"></iconify-icon>
									</span>
									<textarea name="address" class="form-control" rows="3"
										placeholder="Enter full physical address or headquarters location...">{{ $settings['side_section_address'] ?? '' }}</textarea>
								</div>
							</div>

							<!-- Description -->
							<div class="col-12">
								<label class="form-label">Company Description / About</label>
								<textarea name="description" class="form-control" rows="5"
									placeholder="Write a brief description about the company, mission, or side section content...">{{ $settings['side_section_description'] ?? '' }}</textarea>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Sidebar Content: Social Links -->
			<div class="col-lg-4">
				<div class="card h-100">
					<div class="card-header d-flex justify-content-between align-items-center pb-3 border-bottom">
						<h5 class="card-title mb-0">Social Links</h5>
						<button type="button" id="addSocialLinkBtn"
							class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1">
							<iconify-icon icon="lucide:plus"></iconify-icon> Add
						</button>
					</div>
					<div class="card-body" style="background-color: #f8f9fa;">
						<p class="text-sm text-secondary-light mb-3">Add multiple social links dynamically. These will be
							displayed in the side section.</p>

						@php
							$socialLinks = json_decode($settings['side_section_social_links'] ?? '[]', true);
						@endphp
						<div id="socialLinksContainer" class="d-flex flex-column gap-3">
							@if(!empty($socialLinks))
								@foreach($socialLinks as $link)
									<div class="social-link-card bg-white p-3 rounded shadow-sm border border-gray-100 position-relative">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<label class="form-label text-sm fw-semibold mb-0">Platform Name</label>
											<button type="button" class="btn btn-sm text-danger-600 p-0 remove-social-link"
												title="Remove Link">
												<iconify-icon icon="mingcute:delete-2-line" width="18"></iconify-icon>
											</button>
										</div>
										<select name="social_platform[]" class="form-select form-select-sm mb-2">
											<option value="facebook" {{ isset($link['platform']) && $link['platform'] == 'facebook' ? 'selected' : '' }}>Facebook</option>
											<option value="instagram" {{ isset($link['platform']) && $link['platform'] == 'instagram' ? 'selected' : '' }}>Instagram</option>
											<option value="twitter" {{ isset($link['platform']) && $link['platform'] == 'twitter' ? 'selected' : '' }}>Twitter / X</option>
											<option value="linkedin" {{ isset($link['platform']) && $link['platform'] == 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
											<option value="youtube" {{ isset($link['platform']) && $link['platform'] == 'youtube' ? 'selected' : '' }}>YouTube</option>
											<option value="tiktok" {{ isset($link['platform']) && $link['platform'] == 'tiktok' ? 'selected' : '' }}>TikTok</option>
											<option value="pinterest" {{ isset($link['platform']) && $link['platform'] == 'pinterest' ? 'selected' : '' }}>Pinterest</option>
											<option value="website" {{ isset($link['platform']) && $link['platform'] == 'website' ? 'selected' : '' }}>Custom Website</option>
										</select>

										<label class="form-label text-sm fw-semibold mb-1">Profile URL</label>
										<div class="input-group input-group-sm">
											<span class="input-group-text bg-light text-secondary-light border-end-0">
												<iconify-icon icon="lucide:link"></iconify-icon>
											</span>
											<input type="url" name="social_url[]" class="form-control border-start-0 ps-0"
												placeholder="https://..." value="{{ $link['url'] ?? '' }}" required>
										</div>
									</div>
								@endforeach
							@else
								<!-- Default Social Link Item 1 -->
								<div
									class="social-link-card bg-white p-3 rounded shadow-sm border border-gray-100 position-relative">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<label class="form-label text-sm fw-semibold mb-0">Platform Name</label>
										<button type="button" class="btn btn-sm text-danger-600 p-0 remove-social-link"
											title="Remove Link">
											<iconify-icon icon="mingcute:delete-2-line" width="18"></iconify-icon>
										</button>
									</div>
									<select name="social_platform[]" class="form-select form-select-sm mb-2">
										<option value="facebook" selected>Facebook</option>
										<option value="instagram">Instagram</option>
										<option value="twitter">Twitter / X</option>
										<option value="linkedin">LinkedIn</option>
										<option value="youtube">YouTube</option>
										<option value="tiktok">TikTok</option>
										<option value="pinterest">Pinterest</option>
										<option value="website">Custom Website</option>
									</select>

									<label class="form-label text-sm fw-semibold mb-1">Profile URL</label>
									<div class="input-group input-group-sm">
										<span class="input-group-text bg-light text-secondary-light border-end-0">
											<iconify-icon icon="lucide:link"></iconify-icon>
										</span>
										<input type="url" name="social_url[]" class="form-control border-start-0 ps-0"
											placeholder="https://facebook.com/..." required>
									</div>
								</div>
							@endif
						</div>
					</div>
				</div>
			</div>

			<!-- Form Actions -->
			<div class="col-12 mt-4">
				<div class="card">
					<div class="card-body d-flex justify-content-end align-items-center gap-3">
						<button type="reset" class="btn btn-outline-danger-600 px-4 radius-8">Reset</button>
						<button type="submit"
							class="btn btn-primary-600 px-4 radius-8 d-inline-flex align-items-center gap-2">
							<iconify-icon icon="lucide:save"></iconify-icon> Save Settings
						</button>
					</div>
				</div>
			</div>
		</div>
	</form>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// -- 1. Logo Preview Logic --
			const imageUpload = document.getElementById('imageUpload');
			const previewContainer = document.getElementById('imagePreviewContainer');
			const placeholder = document.getElementById('imagePlaceholder');

			imageUpload.addEventListener('change', function (e) {
				const file = e.target.files[0];
				if (file) {
					const reader = new FileReader();
					reader.onload = function (event) {
						// Remove placeholder if it exists
						if (placeholder) {
							placeholder.style.display = 'none';
						}

						// Check if img already exists, else create
						let imgElement = document.getElementById('logoPreviewImg');
						if (!imgElement) {
							imgElement = document.createElement('img');
							imgElement.id = 'logoPreviewImg';
							imgElement.style.maxWidth = '100%';
							imgElement.style.maxHeight = '100%';
							imgElement.style.objectFit = 'contain';
							previewContainer.appendChild(imgElement);
						}

						imgElement.src = event.target.result;
					}
					reader.readAsDataURL(file);
				} else {
					// Reset to placeholder
					const imgElement = document.getElementById('logoPreviewImg');
					if (imgElement) imgElement.remove();
					if (placeholder) placeholder.style.display = 'flex';
				}
			});

			// -- 2. Dynamic Social Links Logic --
			const addBtn = document.getElementById('addSocialLinkBtn');
			const container = document.getElementById('socialLinksContainer');

			addBtn.addEventListener('click', function () {
				const card = document.createElement('div');
				card.className = 'social-link-card bg-white p-3 rounded shadow-sm border border-gray-100 position-relative link-card-anim';
				card.innerHTML = `
						<div class="d-flex justify-content-between align-items-center mb-2">
							<label class="form-label text-sm fw-semibold mb-0">Platform Name</label>
							<button type="button" class="btn btn-sm text-danger-600 p-0 remove-social-link" title="Remove Link">
								<iconify-icon icon="mingcute:delete-2-line" width="18"></iconify-icon>
							</button>
						</div>
						<select name="social_platform[]" class="form-select form-select-sm mb-2">
							<option value="facebook">Facebook</option>
							<option value="instagram">Instagram</option>
							<option value="twitter">Twitter / X</option>
							<option value="linkedin">LinkedIn</option>
							<option value="youtube">YouTube</option>
							<option value="tiktok">TikTok</option>
							<option value="pinterest">Pinterest</option>
							<option value="website">Custom Website</option>
						</select>

						<label class="form-label text-sm fw-semibold mb-1">Profile URL</label>
						<div class="input-group input-group-sm">
							<span class="input-group-text bg-light text-secondary-light border-end-0">
								<iconify-icon icon="lucide:link"></iconify-icon>
							</span>
							<input type="url" name="social_url[]" class="form-control border-start-0 ps-0" placeholder="https://..." required>
						</div>
					`;

				// Add minor entrance animation
				card.style.opacity = '0';
				card.style.transform = 'translateY(-10px)';
				card.style.transition = 'all 0.3s ease-in-out';

				container.appendChild(card);

				// Trigger animation
				setTimeout(() => {
					card.style.opacity = '1';
					card.style.transform = 'translateY(0)';
				}, 10);
			});

			// Delegation for remove buttons
			container.addEventListener('click', function (e) {
				// Find nearest remove button
				const removeBtn = e.target.closest('.remove-social-link');
				if (removeBtn) {
					const card = removeBtn.closest('.social-link-card');

					// Add exit animation
					card.style.transform = 'translateY(-10px)';
					card.style.opacity = '0';
					card.style.transition = 'all 0.3s ease-out';

					setTimeout(() => {
						card.remove();
					}, 300);
				}
			});
		});
	</script>
@endsection
