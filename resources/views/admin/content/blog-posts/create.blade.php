@extends('layouts.admin')
@php
    $title = 'Create Blog Post';
    $subTitle = 'Ecommerce / Blog Posts / Create';
@endphp

@section('content')
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }
        .serp-preview-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            max-width: 650px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .serp-title {
            color: #1a0dab;
            font-size: 18px;
            line-height: 1.3;
            font-weight: 500;
            cursor: pointer;
            word-break: break-word;
        }
        .serp-title:hover {
            text-decoration: underline;
        }
        .serp-url {
            color: #202124;
            font-size: 12px;
            line-height: 1.3;
        }
        .serp-desc {
            color: #4d5156;
            font-size: 13px;
            line-height: 1.4;
            margin-top: 4px;
            word-break: break-word;
        }
    </style>
    @include('admin.partials.messages')

    <div class="card mb-24">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="card-title mb-0">Create Blog Post</h5>
            <a href="{{ route('admin.ecommerce.blog-posts.index') }}" class="btn btn-sm btn-outline-primary-600">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Back to Posts
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.blog-posts.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                
                {{-- Title & Slug --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Blog Title <span class="text-danger">*</span></label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:type"></iconify-icon>
                        </span>
                        <input type="text" name="title" id="blog_title" class="form-control" placeholder="Enter engaging blog title..." value="{{ old('title') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">URL Slug</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:link"></iconify-icon>
                        </span>
                        <input type="text" name="slug" id="blog_slug" class="form-control" placeholder="auto-generated-from-title" value="{{ old('slug') }}">
                    </div>
                    <small class="text-secondary-light text-xs">Auto-generates as you type. URL: <code>/blog/<span id="slug_preview_text">slug</span></code></small>
                </div>

                {{-- Category, Author (Input Box), Status --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="blog_category_id" id="blog_category_id" class="form-select">
                        <option value="">Select Category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('blog_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Author</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:user"></iconify-icon>
                        </span>
                        <input type="text" name="author_name" id="author_name" class="form-control" placeholder="e.g. Dr. Ananya Sharma or NutriBuddy Team" value="{{ old('author_name') }}">
                    </div>
                    <small class="text-secondary-light text-xs">Enter doctor, nutritionist, or author name.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', 'published') == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                {{-- Blog Tags --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Blog Tags</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:tags"></iconify-icon>
                        </span>
                        <input type="text" name="tags" id="tags_input" class="form-control" placeholder="e.g. Kids Nutrition, Immunity Booster, Healthy Recipes, Digestion" value="{{ old('tags') }}">
                    </div>
                    <div id="tags_preview_container" class="d-flex flex-wrap gap-1 mt-2"></div>
                    <small class="text-secondary-light text-xs">Comma-separated tags. They will appear as badges on the blog post.</small>
                </div>

                {{-- Featured Image --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Featured Image</label>
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <input type="file" name="featured_image" id="featured_image_input" class="form-control" accept="image/*">
                            <small class="text-secondary-light text-xs">Recommended dimensions: 1200x630px for crisp social sharing.</small>
                        </div>
                        <div class="flex-shrink-0 border rounded overflow-hidden d-flex align-items-center justify-content-center bg-light" id="featured_image_preview_container" style="display: none !important; width: 100px; height: 100px;">
                            <img id="featured_image_preview" src="" alt="Preview" class="object-fit-cover w-100 h-100">
                        </div>
                    </div>
                </div>

                {{-- Short Description (Card Excerpt - Max 100 words) --}}
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Short Description (Card Excerpt)</label>
                        <small id="short_desc_counter" class="text-secondary-light">0 / 100 words</small>
                    </div>
                    <textarea name="short_description" id="short_description" class="form-control" rows="3" placeholder="Enter a concise summary (max 100 words) displayed on the blog list card...">{{ old('short_description') }}</textarea>
                    <small class="text-secondary-light text-xs">Maximum 100 words. Shown on the blog list cards. If left empty, it will automatically fallback to the first 100 words of the blog content.</small>
                </div>

                {{-- Content Editor --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Blog Content <span class="text-danger">*</span></label>
                    <textarea name="content" id="blog_content" class="form-control" rows="8">{{ old('content') }}</textarea>
                </div>

                {{-- SEO & Metadata Section --}}
                <div class="col-12 mt-4">
                    <div class="card border border-neutral-200">
                        <div class="card-header bg-neutral-50 d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <iconify-icon icon="lucide:search" class="text-primary-600 text-lg"></iconify-icon>
                                <h6 class="mb-0 fw-bold">Search Engine Optimization (SEO) & Social Sharing</h6>
                            </div>
                            <button type="button" id="btn_autofill_seo" class="btn btn-sm btn-outline-primary-600 d-inline-flex align-items-center gap-1">
                                <iconify-icon icon="lucide:sparkles"></iconify-icon> Auto-fill from Title & Content
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-semibold mb-0">Meta Title</label>
                                        <small id="meta_title_counter" class="text-secondary-light">0 / 60</small>
                                    </div>
                                    <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Catchy title for Google search..." value="{{ old('meta_title') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-1">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" placeholder="kids nutrition, baby food, healthy gut" value="{{ old('meta_keywords') }}">
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-semibold mb-0">Meta Description</label>
                                        <small id="meta_desc_counter" class="text-secondary-light">0 / 160</small>
                                    </div>
                                    <textarea name="meta_description" id="meta_description" class="form-control" rows="3" placeholder="Compelling 150-160 character summary for search engine results...">{{ old('meta_description') }}</textarea>
                                </div>

                                {{-- Google SERP Snippet Preview --}}
                                <div class="col-12 mt-3">
                                    <label class="form-label fw-semibold text-dark">Google Search Result Preview</label>
                                    <div class="serp-preview-box">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 13px;">🥗</span>
                                            <div>
                                                <div class="serp-url fw-medium">NutriBuddy Kids</div>
                                                <div class="serp-url text-muted">{{ url('/blog') }}/<span id="serp_slug">post-slug</span></div>
                                            </div>
                                        </div>
                                        <div class="serp-title" id="serp_title">Blog Title — NutriBuddy Kids</div>
                                        <div class="serp-desc" id="serp_desc">Write a meta description or blog content to preview how this article will look on Google search results.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <a href="{{ route('admin.ecommerce.blog-posts.index') }}" class="btn btn-light px-32 fw-bold">Cancel</a>
                        <button type="submit" class="btn btn-primary-600 px-40 fw-bold d-inline-flex align-items-center gap-2">
                            <iconify-icon icon="lucide:check-circle" style="font-size: 18px; line-height: 1;"></iconify-icon>
                            <span>Create & Publish Post</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let editorInstance = null;

            // Initialize CKEditor
            ClassicEditor
                .create(document.querySelector('#blog_content'), {
                    ckfinder: {
                        uploadUrl: '{{ route('admin.ecommerce.blog-posts.upload-image') }}?_token={{ csrf_token() }}'
                    }
                })
                .then(editor => {
                    editorInstance = editor;
                })
                .catch(error => {
                    console.error(error);
                });

            // Elements
            const titleInput = document.getElementById('blog_title');
            const slugInput = document.getElementById('blog_slug');
            const slugPreviewText = document.getElementById('slug_preview_text');
            const serpSlug = document.getElementById('serp_slug');
            const serpTitle = document.getElementById('serp_title');
            const serpDesc = document.getElementById('serp_desc');
            const metaTitle = document.getElementById('meta_title');
            const metaDesc = document.getElementById('meta_description');
            const metaTitleCounter = document.getElementById('meta_title_counter');
            const metaDescCounter = document.getElementById('meta_desc_counter');
            const tagsInput = document.getElementById('tags_input');
            const tagsPreview = document.getElementById('tags_preview_container');
            const btnAutofillSeo = document.getElementById('btn_autofill_seo');

            let userEditedSlug = Boolean(slugInput.value.trim());

            // Slugify helper
            function slugify(text) {
                return text
                    .toString()
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, '-')           // Replace spaces with -
                    .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                    .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                    .replace(/^-+/, '')             // Trim - from start of text
                    .replace(/-+$/, '');            // Trim - from end of text
            }

            // Sync slug
            function updateSlug(force = false) {
                if (!userEditedSlug || force) {
                    const generated = slugify(titleInput.value);
                    slugInput.value = generated;
                    slugPreviewText.textContent = generated || 'slug';
                    serpSlug.textContent = generated || 'post-slug';
                    if (force) userEditedSlug = false;
                } else {
                    slugPreviewText.textContent = slugInput.value || 'slug';
                    serpSlug.textContent = slugInput.value || 'post-slug';
                }
            }

            titleInput.addEventListener('input', function() {
                updateSlug();
                updateSerp();
            });

            slugInput.addEventListener('input', function() {
                userEditedSlug = true;
                slugInput.value = slugify(slugInput.value);
                slugPreviewText.textContent = slugInput.value || 'slug';
                serpSlug.textContent = slugInput.value || 'post-slug';
            });

            // Tags live chip preview
            function updateTagsPreview() {
                const raw = tagsInput.value.trim();
                tagsPreview.innerHTML = '';
                if (!raw) return;

                const tags = raw.split(',').map(t => t.trim()).filter(t => t.length > 0);
                tags.forEach(tag => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary-100 text-primary-600 px-2 py-1 radius-4 font-semibold text-xs';
                    badge.textContent = '#' + tag;
                    tagsPreview.appendChild(badge);
                });
            }

            tagsInput.addEventListener('input', updateTagsPreview);
            updateTagsPreview();

            // SERP & Character Counters
            function updateSerp() {
                const titleText = metaTitle.value.trim() || titleInput.value.trim() || 'Blog Title';
                serpTitle.textContent = titleText + ' — NutriBuddy Kids';

                const descText = metaDesc.value.trim() || 'Write a meta description or blog content to preview how this article will look on Google search results.';
                serpDesc.textContent = descText;

                // Counters
                const titleLen = metaTitle.value.length;
                metaTitleCounter.textContent = `${titleLen} / 60`;
                metaTitleCounter.className = titleLen > 60 ? 'text-danger' : (titleLen >= 50 ? 'text-success' : 'text-secondary-light');

                const descLen = metaDesc.value.length;
                metaDescCounter.textContent = `${descLen} / 160`;
                metaDescCounter.className = descLen > 160 ? 'text-danger' : (descLen >= 140 ? 'text-success' : 'text-secondary-light');
            }

            metaTitle.addEventListener('input', updateSerp);
            metaDesc.addEventListener('input', updateSerp);

            // Short Description Word Counter (100 words limit)
            const shortDescInput = document.getElementById('short_description');
            const shortDescCounter = document.getElementById('short_desc_counter');

            function countWords(str) {
                return (str.trim().match(/\S+/g) || []).length;
            }

            function updateShortDescCounter() {
                if (!shortDescInput || !shortDescCounter) return;
                const words = countWords(shortDescInput.value);
                shortDescCounter.textContent = `${words} / 100 words`;
                if (words > 100) {
                    shortDescCounter.className = 'text-danger fw-bold';
                } else if (words >= 80) {
                    shortDescCounter.className = 'text-warning fw-semibold';
                } else {
                    shortDescCounter.className = 'text-secondary-light';
                }
            }

            if (shortDescInput) {
                shortDescInput.addEventListener('input', updateShortDescCounter);
                updateShortDescCounter();
            }

            // Autofill SEO button
            btnAutofillSeo.addEventListener('click', function() {
                if (!metaTitle.value && titleInput.value) {
                    metaTitle.value = titleInput.value.substring(0, 60);
                }

                if (!metaDesc.value) {
                    if (shortDescInput && shortDescInput.value.trim()) {
                        metaDesc.value = shortDescInput.value.trim().substring(0, 160);
                    } else if (editorInstance) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = editorInstance.getData();
                        const cleanText = (tempDiv.textContent || tempDiv.innerText || '').trim().replace(/\s+/g, ' ');
                        metaDesc.value = cleanText.substring(0, 160);
                    }
                }

                if (!document.getElementById('meta_keywords').value && tagsInput.value) {
                    document.getElementById('meta_keywords').value = tagsInput.value;
                }

                updateSerp();
            });

            // Image Preview
            const imgInput = document.getElementById('featured_image_input');
            const imgPreviewContainer = document.getElementById('featured_image_preview_container');
            const imgPreview = document.getElementById('featured_image_preview');

            imgInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result;
                        imgPreviewContainer.style.setProperty('display', 'block', 'important');
                    }
                    reader.readAsDataURL(file);
                } else {
                    imgPreview.src = '';
                    imgPreviewContainer.style.setProperty('display', 'none', 'important');
                }
            });

            // Initial trigger
            updateSlug();
            updateSerp();
        });
    </script>
@endsection
