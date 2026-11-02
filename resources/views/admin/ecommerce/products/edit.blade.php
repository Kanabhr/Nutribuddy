@extends('layouts.admin')
@php
    $title = 'Edit Product';
    $subTitle = 'Ecommerce / Products / Edit';
    $psDefaults = $problemSolutionDefaults ?? [];
    $storageOrAsset = function (?string $path): ?string {
        if (!$path) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($path, ['img/', 'assets/'])
            ? asset($path)
            : asset('storage/' . $path);
    };
    $psTaglineItems = old('ps_tagline_items', $product->ps_tagline_items ?: ($psDefaults['tagline_items'] ?? []));
    $psLeftCards = old('ps_left_cards', $product->ps_left_cards ?: ($psDefaults['left_cards'] ?? []));
    $psRightCards = old('ps_right_cards', $product->ps_right_cards ?: ($psDefaults['right_cards'] ?? []));
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_type" value="{{ $product->product_type }}">

                <!-- 1. GENERAL INFORMATION -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">General Information</h5>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" placeholder="e.g. GrowStrong Gummies" required>
                                @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SKU Code</label>
                                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control" placeholder="Auto generated if empty">
                                @error('sku')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">HSN Code</label>
                                <input type="text" name="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}" class="form-control" placeholder="e.g. 2106">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tax Rate</label>
                                <select name="tax_rate_id" class="form-select">
                                    <option value="">No Tax / Exempt</option>
                                    @foreach ($taxRates as $tax)
                                        <option value="{{ $tax->id }}" {{ old('tax_rate_id', $product->tax_rate_id) == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. PRICING & STOCK -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">Pricing & Inventory</h5>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">MRP / Compare (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input type="number" step="0.01" min="0" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" class="form-control border-start-0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-success">Selling Price (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price', $product->base_price) }}" class="form-control border-start-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Shipping (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input type="number" step="0.01" min="0" name="shipping_price" value="{{ old('shipping_price', $product->shipping_price ?? 0) }}" class="form-control border-start-0" placeholder="0.00">
                                </div>
                            </div>
                            
                            <div class="col-md-6" id="parentStockGroup">
                                <label class="form-label fw-bold">Current Stock Quantity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><iconify-icon icon="solar:box-linear"></iconify-icon></span>
                                    <input type="number" name="stock_qty" id="productStockInput" value="{{ old('stock_qty', $product->inventory?->stock_qty ?? 0) }}" class="form-control" min="0">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-warning">NB Coins Reward 🪙</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">🪙</span>
                                    <input type="number" name="coins_reward" value="{{ old('coins_reward', $product->coins_reward ?? 0) }}" class="form-control border-start-0" placeholder="e.g. 50">
                                </div>
                                <small class="text-muted">Coins awarded to customer after purchase</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dosage</label>
                                <input type="text" name="dosage" value="{{ old('dosage', $product->dosage) }}" class="form-control" placeholder="e.g. 1 Gummy daily">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Routine</label>
                                <input type="text" name="routine" value="{{ old('routine', $product->routine) }}" class="form-control" placeholder="e.g. Morning after breakfast">
                                @error('routine')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-end gap-24">
                                <input type="hidden" name="track_stock" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="track_stock" value="1" id="track_stock" {{ old('track_stock', $product->inventory?->track_stock ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label m-0 fw-semibold" for="track_stock">Track Inventory</label>
                                </div>
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label m-0 fw-semibold" for="is_active">Active</label>
                                </div>
                                <input type="hidden" name="is_featured" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                                    <input class="form-check-input m-0 float-none" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                    <label class="form-check-label m-0 fw-semibold" for="is_featured">Featured</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('admin.ecommerce.products._attribute_variations', ['attributes' => $attributes, 'product' => $product])

                <!-- 4. DESCRIPTIONS -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">Content & Descriptions</h5>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold">Full Detailed Description</label>
                                <textarea name="description" id="editor" class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. CARD MEDIA -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">Product Card Images</h5>
                        <small class="text-secondary">Shown on home and product listing cards only</small>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Default Card Image</label>
                                @if($product->card_image_path)
                                    <div class="mb-12 radius-12 overflow-hidden border bg-white shadow-sm" style="width:140px;">
                                        <img src="{{ asset('storage/' . $product->card_image_path) }}" class="w-100 object-fit-cover" style="aspect-ratio:1/1;">
                                    </div>
                                @endif
                                <input type="file" name="card_image" class="form-control" accept="image/*">
                                <small class="text-muted">Replace the image used first on index and product listing pages.</small>
                                @error('card_image')<span class="text-danger small d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hover Card Image</label>
                                @if($product->card_hover_image_path)
                                    <div class="mb-12 radius-12 overflow-hidden border bg-white shadow-sm" style="width:140px;">
                                        <img src="{{ asset('storage/' . $product->card_hover_image_path) }}" class="w-100 object-fit-cover" style="aspect-ratio:1/1;">
                                    </div>
                                @endif
                                <input type="file" name="card_hover_image" class="form-control" accept="image/*">
                                <small class="text-muted">Replace the hover image. If empty, the default image is reused.</small>
                                @error('card_hover_image')<span class="text-danger small d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. MEDIA -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">Product Gallery</h5>
                        <small class="text-secondary">Shown on product detail page gallery</small>
                    </div>
                    <div class="card-body p-24">
                        <div class="upload-area border-dashed radius-12 p-32 text-center cursor-pointer bg-white transition-base border-2"
                            onclick="document.getElementById('images').click()" id="dropZone">
                            <input type="file" name="images[]" id="images" class="d-none" multiple accept="image/*">
                            <div class="mb-12">
                                <iconify-icon icon="solar:camera-add-bold" class="text-primary-600 display-4"></iconify-icon>
                            </div>
                            <h6 class="mb-4 text-dark fw-bold">Click to upload or drag & drop</h6>
                            <p class="text-secondary small mb-0">Up to 5MB each (JPG, PNG, WebP)</p>
                        </div>
                        
                        <!-- Existing Images -->
                        <div id="existingImages" class="row g-3 mt-16">
                            @foreach($product->images as $img)
                                <div class="col-3 col-md-2 position-relative">
                                    <div class="radius-12 overflow-hidden border bg-white shadow-sm h-100">
                                        <img src="{{ asset('storage/' . $img->image_path) }}" class="w-100 h-100 object-fit-cover" style="aspect-ratio:1/1;">
                                    </div>
                                    <button type="button" class="gallery-delete-btn position-absolute top-0 end-0 m-4 shadow-sm" onclick="if(confirm('Delete image?')){document.getElementById('delete-image-{{ $img->id }}').submit()}" aria-label="Delete image">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div id="imagePreview" class="row g-3 mt-16"></div>
                    </div>
                </div>

                <!-- 6. PRODUCT FEATURES -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom d-flex justify-content-between align-items-center py-16 px-24">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-100 text-primary-600 p-2 rounded-2">
                                <iconify-icon icon="lucide:sparkles" class="fs-5"></iconify-icon>
                            </span>
                            <div>
                                <h5 class="card-title mb-0">Product Features</h5>
                                <small class="text-secondary">Key features shown on the product card and detail page</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary-600 btn-sm px-3 d-inline-flex align-items-center gap-1" id="addTagBtn">
                            <iconify-icon icon="lucide:plus" style="font-size: 16px;"></iconify-icon> Add Feature
                        </button>
                    </div>
                    <div class="card-body p-24">
                        <div id="tagsWrapper">
                            <!-- Feature rows injected here -->
                        </div>
                        <div id="noTagsMessage" class="text-center py-5 text-muted">
                            <iconify-icon icon="lucide:list-checks" class="fs-1 opacity-25"></iconify-icon>
                            <p class="mt-2 mb-0 small">No features yet &mdash; click <strong>Add Feature</strong> to get started.</p>
                        </div>
                    </div>
                </div>

                @include('admin.ecommerce.products.partials.transform-section-fields', ['product' => $product])

                <!-- 6. KIDS FACE REAL PROBLEMS -->
                <div class="card border-0 radius-12 mb-24 shadow-sm">
                    <div class="card-header bg-base border-bottom d-flex justify-content-between align-items-center py-16 px-24">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-100 text-warning-600 p-2 rounded-2">
                                <iconify-icon icon="lucide:alert-circle" class="fs-5"></iconify-icon>
                            </span>
                            <div>
                                <h5 class="card-title mb-0">Kids Face Real Problems</h5>
                                <small class="text-secondary">Headers and 4 dynamic problem cards</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Section Heading</label>
                                <input type="text" name="problem_heading" value="{{ old('problem_heading', $product->problem_heading ?? 'The Real Picture') }}" class="form-control" placeholder="e.g. The Real Picture">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Section Subtitle</label>
                                <textarea name="problem_subtitle" class="form-control" rows="1" placeholder="e.g. Today's kids miss out...">{{ old('problem_subtitle', $product->problem_subtitle ?? "Today's kids miss out on essential nutrition every day. We see the gap — and we've closed it.") }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Main Title (HTML allowed for styling)</label>
                                <input type="text" name="problem_title" value="{{ old('problem_title', $product->problem_title ?? 'Kids Face <span class=&quot;acc&quot;>Real Problems</span> <br>We Built a <span class=&quot;acc2&quot;>Real Solution</span>') }}" class="form-control" placeholder="Main big title...">
                                <small class="text-muted">Use &lt;span class="acc"&gt; and &lt;span class="acc2"&gt; for colored text.</small>
                            </div>
                        </div>

                        <div class="row g-4">
                            @for($i = 0; $i < 4; $i++)
                            <div class="col-md-6">
                                <div class="p-16 border rounded bg-light-soft">
                                    <h6 class="mb-3">Problem Card {{ $i + 1 }}</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Title</label>
                                        <input type="text" name="problem_cards[{{ $i }}][title]" value="{{ old('problem_cards.'.$i.'.title', $product->problem_cards[$i]['title'] ?? '') }}" class="form-control" placeholder="e.g. Vitamin & Mineral Deficiency">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea name="problem_cards[{{ $i }}][description]" class="form-control" rows="2" placeholder="e.g. Processed food strips away nutrients...">{{ old('problem_cards.'.$i.'.description', $product->problem_cards[$i]['description'] ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold">Image</label>
                                        @if(isset($product->problem_cards[$i]['icon']) && $product->problem_cards[$i]['icon'])
                                            <div class="mb-2 position-relative d-inline-block">
                                                <img src="{{ Str::startsWith($product->problem_cards[$i]['icon'], ['img/', 'assets/']) ? asset($product->problem_cards[$i]['icon']) : asset('storage/' . $product->problem_cards[$i]['icon']) }}" alt="Icon" style="height: 40px; border-radius: 4px; object-fit: contain;">
                                                <button type="button" class="btn btn-danger btn-xs position-absolute top-0 start-100 translate-middle rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:20px;height:20px; z-index:10;" onclick="if(confirm('Remove this image?')){document.getElementById('delete-problem-icon-{{$i}}').value='1'; this.parentElement.style.display='none';}" title="Remove image">
                                                    <i class="fas fa-times text-xs" style="font-size: 14px;"></i>
                                                </button>
                                                <input type="hidden" name="delete_problem_card_icon[{{ $i }}]" id="delete-problem-icon-{{$i}}" value="0">
                                            </div>
                                            <input type="hidden" name="problem_cards[{{ $i }}][icon]" value="{{ $product->problem_cards[$i]['icon'] }}">
                                        @endif
                                        <input type="file" name="problem_card_images[{{ $i }}]" class="form-control" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- 6.5. NUTRIBUDDY BENEFITS -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom d-flex justify-content-between align-items-center py-16 px-24">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-100 text-success-600 p-2 rounded-2">
                                <iconify-icon icon="lucide:heart-pulse" class="fs-5"></iconify-icon>
                            </span>
                            <div>
                                <h5 class="card-title mb-0">The Nutribuddy Benefits</h5>
                                <small class="text-secondary">4 dynamic cards shown at the bottom of the product page</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            @for($i = 0; $i < 4; $i++)
                            <div class="col-md-6">
                                <div class="p-16 border rounded bg-light-soft">
                                    <h6 class="mb-3">Benefit Card {{ $i + 1 }}</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Title</label>
                                        <input type="text" name="benefits_cards[{{ $i }}][title]" value="{{ old('benefits_cards.'.$i.'.title', $product->benefits_cards[$i]['title'] ?? '') }}" class="form-control" placeholder="e.g. Strong Body & Healthy Growth">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea name="benefits_cards[{{ $i }}][description]" class="form-control" rows="2" placeholder="e.g. Essential vitamins and minerals...">{{ old('benefits_cards.'.$i.'.description', $product->benefits_cards[$i]['description'] ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold">Icon Image</label>
                                        @if(isset($product->benefits_cards[$i]['icon']) && $product->benefits_cards[$i]['icon'])
                                            <div class="mb-2 position-relative d-inline-block">
                                                <img src="{{ asset('storage/' . $product->benefits_cards[$i]['icon']) }}" alt="Icon" style="height: 40px; border-radius: 4px; object-fit: contain;">
                                                <button type="button" class="btn btn-danger btn-xs position-absolute top-0 start-100 translate-middle rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:20px;height:20px; z-index:10;" onclick="if(confirm('Remove this icon?')){document.getElementById('delete-benefit-icon-{{$i}}').value='1'; this.parentElement.style.display='none';}" title="Remove icon">
                                                    <i class="fas fa-times text-xs" style="font-size: 14px;"></i>
                                                </button>
                                                <input type="hidden" name="delete_benefits_card_icon[{{ $i }}]" id="delete-benefit-icon-{{$i}}" value="0">
                                            </div>
                                            <input type="hidden" name="benefits_cards[{{ $i }}][icon]" value="{{ $product->benefits_cards[$i]['icon'] }}">
                                        @endif
                                        <input type="file" name="benefits_card_images[{{ $i }}]" class="form-control" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- 7. SEO -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">SEO Settings (Google Search)</h5>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Meta Title</label>
                                <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="form-control" placeholder="Title for Search Engines">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Meta Keywords</label>
                                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" class="form-control" placeholder="Keyword1, Keyword2, ...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief summary for Google results">{{ old('meta_description', $product->meta_description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. PROBLEM SOLUTION SECTION -->
                <div class="card border-0 radius-12 mb-24">
                    <div class="card-header bg-base border-bottom py-16 px-24">
                        <h5 class="card-title mb-0">Problem Solution Section</h5>
                        <p class="text-secondary-light mb-0 mt-1">Dynamic content shown on this product detail page.</p>
                    </div>
                    <div class="card-body p-24">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Main Heading</label>
                                <input type="text" name="ps_brand_title" class="form-control" value="{{ old('ps_brand_title', $product->ps_brand_title ?: ($psDefaults['brand_title'] ?? 'Nutribuddy')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Product Heading</label>
                                <input type="text" name="ps_product_title" class="form-control" value="{{ old('ps_product_title', $product->ps_product_title ?: ($psDefaults['product_title'] ?? $product->name)) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Small Tagline Items</label>
                                <div class="row g-3">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="col-md-4">
                                            <input type="text" name="ps_tagline_items[]" class="form-control" value="{{ $psTaglineItems[$i] ?? '' }}" placeholder="e.g. Daily Nutrition">
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Center Product Image</label>
                                <input type="hidden" name="ps_center_image" value="{{ old('ps_center_image', $product->ps_center_image) }}">
                                <input type="file" name="ps_center_image_file" class="form-control" accept="image/*">
                                @php $centerPreview = $storageOrAsset($product->ps_center_image ?: ($psDefaults['center_image'] ?? null)); @endphp
                                @if ($centerPreview)
                                    <img src="{{ $centerPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 160px; height: 160px;" alt="">
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bottom Left Image</label>
                                <input type="hidden" name="ps_shelf_left_image" value="{{ old('ps_shelf_left_image', $product->ps_shelf_left_image) }}">
                                <input type="file" name="ps_shelf_left_image_file" class="form-control" accept="image/*">
                                @php $leftShelfPreview = $storageOrAsset($product->ps_shelf_left_image ?: ($psDefaults['shelf_left_image'] ?? null)); @endphp
                                @if ($leftShelfPreview)
                                    <img src="{{ $leftShelfPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 140px; height: 120px;" alt="">
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bottom Right Image</label>
                                <input type="hidden" name="ps_shelf_right_image" value="{{ old('ps_shelf_right_image', $product->ps_shelf_right_image) }}">
                                <input type="file" name="ps_shelf_right_image_file" class="form-control" accept="image/*">
                                @php $rightShelfPreview = $storageOrAsset($product->ps_shelf_right_image ?: ($psDefaults['shelf_right_image'] ?? null)); @endphp
                                @if ($rightShelfPreview)
                                    <img src="{{ $rightShelfPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 140px; height: 120px;" alt="">
                                @endif
                            </div>
                        </div>

                        <div class="row g-4 mt-1">
                            <div class="col-lg-6">
                                <div class="border rounded-3 h-100">
                                    <div class="border-bottom py-14 px-16 d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">Left Ingredient Cards</h6>
                                            <small class="text-secondary-light">Power of Nature side</small>
                                        </div>
                                        <button type="button" class="btn btn-success-600 btn-sm radius-8" data-add-ps-row="left">Add Item</button>
                                    </div>
                                    <div class="p-16">
                                        <label class="form-label fw-bold">Left Label</label>
                                        <textarea name="ps_left_label" rows="2" class="form-control mb-20">{{ old('ps_left_label', $product->ps_left_label ?: ($psDefaults['left_label'] ?? '')) }}</textarea>

                                        <div data-ps-list="left">
                                            @foreach ($psLeftCards as $index => $card)
                                                @include('admin.ecommerce.products.partials.problem-solution-card-row', [
                                                    'side' => 'left',
                                                    'index' => $index,
                                                    'card' => $card,
                                                    'storageOrAsset' => $storageOrAsset,
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="border rounded-3 h-100">
                                    <div class="border-bottom py-14 px-16 d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">Right Feature Cards</h6>
                                            <small class="text-secondary-light">Daily Goodness side</small>
                                        </div>
                                        <button type="button" class="btn btn-success-600 btn-sm radius-8" data-add-ps-row="right">Add Item</button>
                                    </div>
                                    <div class="p-16">
                                        <label class="form-label fw-bold">Right Label</label>
                                        <textarea name="ps_right_label" rows="2" class="form-control mb-20">{{ old('ps_right_label', $product->ps_right_label ?: ($psDefaults['right_label'] ?? '')) }}</textarea>

                                        <div data-ps-list="right">
                                            @foreach ($psRightCards as $index => $card)
                                                @include('admin.ecommerce.products.partials.problem-solution-card-row', [
                                                    'side' => 'right',
                                                    'index' => $index,
                                                    'card' => $card,
                                                    'storageOrAsset' => $storageOrAsset,
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SAVE BUTTON -->
                <div class="card bg-white border-0 shadow-lg sticky-bottom py-20 px-32 radius-16 mb-40">
                    <div class="d-flex justify-content-end gap-16">
                        <a href="{{ route('admin.ecommerce.products.index') }}" class="btn btn-light px-32 fw-bold">Discard Changes</a>
                        <button type="submit" class="btn btn-primary-600 px-40 fw-bold d-flex align-items-center gap-2">
                            <iconify-icon icon="lucide:save" style="font-size:18px; line-height:1;"></iconify-icon>
                            <span>UPDATE PRODUCT</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @foreach ($product->images as $image)
        <form id="delete-image-{{ $image->id }}" action="{{ route('admin.ecommerce.products.images.destroy', $image) }}" method="POST" class="d-none">
            @csrf @method('DELETE')
        </form>
    @endforeach

    <!-- SCRIPTS -->
    <template id="psRowTemplate">
        <div class="ps-card-row border rounded-3 p-3 mb-3 bg-light">
            <input type="hidden" data-name="icon" value="">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="border rounded-3 bg-white d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                        <iconify-icon icon="lucide:image" class="text-secondary-light"></iconify-icon>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <input type="text" data-name="title" class="form-control mb-2" placeholder="Title">
                    <textarea data-name="text" rows="2" class="form-control mb-2" placeholder="Description"></textarea>
                    <input type="file" data-name="image" class="form-control" accept="image/*">
                </div>
                <button type="button" class="btn btn-outline-danger-600 btn-sm radius-8" data-remove-ps-row>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </template>

    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    <script>
        ClassicEditor.create(document.querySelector('#editor')).catch(e => console.error(e));

        function renameProblemSolutionRow(row, side, index) {
            row.querySelector('[data-name="icon"]').name = `ps_${side}_cards[${index}][icon]`;
            row.querySelector('[data-name="title"]').name = `ps_${side}_cards[${index}][title]`;
            row.querySelector('[data-name="text"]').name = `ps_${side}_cards[${index}][text]`;
            row.querySelector('[data-name="image"]').name = `ps_${side}_card_images[${index}]`;
        }

        function reindexProblemSolutionRows(side) {
            document.querySelectorAll(`[data-ps-list="${side}"] .ps-card-row`).forEach(function (row, index) {
                renameProblemSolutionRow(row, side, index);
            });
        }

        document.querySelectorAll('[data-add-ps-row]').forEach(function (button) {
            button.addEventListener('click', function () {
                const side = button.dataset.addPsRow;
                const list = document.querySelector(`[data-ps-list="${side}"]`);
                const row = document.getElementById('psRowTemplate').content.firstElementChild.cloneNode(true);
                list.appendChild(row);
                reindexProblemSolutionRows(side);
            });
        });

        document.addEventListener('click', function (event) {
            const removeButton = event.target.closest('[data-remove-ps-row]');
            if (!removeButton) return;

            const row = removeButton.closest('.ps-card-row');
            const list = row.closest('[data-ps-list]');
            const side = list.dataset.psList;
            row.remove();
            reindexProblemSolutionRows(side);
        });

        // ============================================================
        // TAGS MANAGEMENT
        // ============================================================
        let tagCount = 0;
        const tagsWrapper   = document.getElementById('tagsWrapper');
        const noTagsMessage = document.getElementById('noTagsMessage');
        const addTagBtn     = document.getElementById('addTagBtn');

        function syncNoTagsMessage() {
            noTagsMessage.style.display = tagsWrapper.children.length > 0 ? 'none' : 'block';
        }

        function addTagRow(iconPath = '', text = '') {
            const index = tagCount++;
            const row = document.createElement('div');
            row.className = 'tag-row admin-feature-row d-grid align-items-center gap-3 px-3 py-2 border-bottom';
            row.innerHTML = `
                <div class="position-relative flex-shrink-0">
                    <div class="tag-icon-preview rounded-2 border bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width:48px;height:48px;">
                        ${iconPath
                            ? `<img src="/storage/${iconPath}" class="w-100 h-100 object-fit-contain">`
                            : `<iconify-icon icon="lucide:image" class="fs-4 text-muted"></iconify-icon>`}
                    </div>
                    <label class="position-absolute bottom-0 end-0 mb-n1 me-n1 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center cursor-pointer" style="width:18px;height:18px;" title="Upload icon">
                        <iconify-icon icon="lucide:pencil" style="font-size:9px;"></iconify-icon>
                        <input type="file" name="tag_images[${index}]" class="d-none tag-image-input" accept="image/*">
                    </label>
                    <input type="hidden" name="tags[${index}][icon]" value="${iconPath}" class="tag-icon-hidden">
                </div>
                <div class="min-w-0">
                    <input type="text" name="tags[${index}][text]" value="${text}"
                        class="form-control form-control-sm"
                        placeholder="e.g. No Added Sugar" required>
                </div>
                <button type="button" class="admin-feature-delete remove-tag-row" title="Delete feature" aria-label="Delete feature">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            tagsWrapper.appendChild(row);
            syncNoTagsMessage();

            row.querySelector('.tag-image-input').addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        row.querySelector('.tag-icon-preview').innerHTML =
                            `<img src="${e.target.result}" class="w-100 h-100 object-fit-contain">`;
                        row.querySelector('.tag-icon-hidden').value = '';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            row.querySelector('.remove-tag-row').addEventListener('click', () => {
                row.remove();
                syncNoTagsMessage();
            });
        }

        @php
            $currentTags = $product->tags;
            if (is_string($currentTags)) {
                $currentTags = array_map(function($t) {
                    preg_match('/^([\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}])?\s*(.*)$/u', $t, $m);
                    return ['icon' => $m[1] ?? '', 'text' => $m[2] ?? $t];
                }, array_filter(array_map('trim', explode(',', $currentTags))));
            }
        @endphp

        @if(!empty($currentTags) && is_array($currentTags))
            @foreach($currentTags as $tag)
                addTagRow('{{ addslashes($tag['icon'] ?? '') }}', '{{ addslashes($tag['text'] ?? '') }}');
            @endforeach
        @endif

        addTagBtn.addEventListener('click', () => addTagRow());


        // Advanced variants JS removed

        // GALLERY PREVIEW WITH REMOVE OPTION
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('imagePreview');
        const dropZone = document.getElementById('dropZone');
        let selectedFiles = [];

        function renderPreviews() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const col = document.createElement('div');
                    col.className = 'col-4 col-md-3 col-lg-2';
                    col.innerHTML = `
                        <div class="position-relative group radius-12 overflow-hidden border shadow-sm h-100 bg-white">
                            <img src="${e.target.result}" class="w-100 h-100 object-fit-cover" style="aspect-ratio:1/1;">
                            <button type="button" class="btn btn-danger btn-xs position-absolute top-0 end-0 m-2 p-0 radius-circle d-flex align-items-center justify-content-center shadow-sm remove-img" 
                                style="width:24px;height:24px; z-index: 10;" data-index="${index}">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                            <div class="position-absolute bottom-0 start-0 w-100 p-1 bg-dark bg-opacity-50 text-white text-xxs text-truncate">
                                ${file.name}
                            </div>
                        </div>
                    `;
                    previewContainer.appendChild(col);

                    col.querySelector('.remove-img').addEventListener('click', function(e) {
                        e.stopPropagation();
                        removeFile(index);
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateInputFiles();
            renderPreviews();
        }

        function updateInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            imageInput.files = dataTransfer.files;
        }

        imageInput.addEventListener('change', function() {
            const newFiles = Array.from(this.files);
            selectedFiles = [...selectedFiles, ...newFiles];
            updateInputFiles();
            renderPreviews();
        });

        // Drag and Drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('border-primary'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-primary'), false);
        });

        dropZone.addEventListener('drop', e => {
            const droppedFiles = Array.from(e.dataTransfer.files);
            selectedFiles = [...selectedFiles, ...droppedFiles];
            updateInputFiles();
            renderPreviews();
        }, false);
    </script>

    <style>
        .form-label { font-size: 0.8125rem; margin-bottom: 0.5rem; }
        .card { border-radius: 12px; }
        .bg-light-soft { background-color: #f8fafc; }
        .btn-soft-danger { background: #fee2e2; color: #dc2626; }
        .btn-soft-primary { background: #eef2ff; color: #4f46e5; }
        .upload-area:hover { background: #fff; border-color: var(--primary); }
        .radius-20 { border-radius: 20px; }
        .btn-xs { padding: 4px 8px; font-size: 11px; }
        .text-xxs { font-size: 10px; }
        .sticky-bottom { position: sticky; bottom: 20px; z-index: 1000; }
        .tag-item .card { transition: box-shadow 0.2s ease, transform 0.2s ease; }
        .tag-item .card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1) !important; transform: translateY(-2px); }
        .admin-feature-row { grid-template-columns: 60px minmax(0, 1fr) 44px; }
        .admin-feature-delete {
            width: 32px;
            height: 32px;
            padding: 0;
            border: none;
            background: #ffe5e5;
            color: #dc3545;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .admin-feature-delete:hover {
            background: #dc3545;
            color: white;
        }
        .gallery-delete-btn {
            width: 24px;
            height: 24px;
            border: 2px solid #fff;
            border-radius: 50%;
            background: #ef4444;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
            cursor: pointer;
            z-index: 5;
            transition: background .18s ease, transform .18s ease;
        }
        .gallery-delete-btn span {
            color: #fff;
            font-size: 20px;
            font-weight: 800;
            line-height: 18px;
            transform: translateY(-1px);
        }
        .gallery-delete-btn:hover {
            background: #dc2626;
            transform: scale(1.06);
        }
        .min-w-0 { min-width: 0; }
        @media (max-width: 575px) {
            .admin-feature-row {
                grid-template-columns: 52px minmax(0, 1fr) 40px;
                gap: 10px !important;
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .admin-feature-delete {
                width: 36px;
                height: 36px;
            }
        }
        .radius-12 { border-radius: 12px; }
        .radius-circle { border-radius: 50%; }
    </style>
@endsection
