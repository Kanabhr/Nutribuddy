@extends('layouts.admin')
@php
    $title = 'Problem Solution';
    $subTitle = 'Ecommerce / Products / Problem Solution';
    $isProblemSolutionHub = $isProblemSolutionHub ?? false;
    $products = $products ?? collect();

    $storageOrAsset = function (?string $path): ?string {
        if (!$path) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($path, ['img/', 'assets/'])
            ? asset($path)
            : asset('storage/' . $path);
    };

    $taglineItems = $product ? old('ps_tagline_items', $product->ps_tagline_items ?: $defaults['tagline_items']) : $defaults['tagline_items'];
    $leftCards = $product ? old('ps_left_cards', $product->ps_left_cards ?: $defaults['left_cards']) : $defaults['left_cards'];
    $rightCards = $product ? old('ps_right_cards', $product->ps_right_cards ?: $defaults['right_cards']) : $defaults['right_cards'];
@endphp

@section('content')
    @include('admin.partials.messages')

    @if (!$product)
        <div class="card border-0 radius-12">
            <div class="card-body p-24 text-center">
                <h5 class="mb-2">No products found</h5>
                <p class="text-secondary-light mb-0">Create a product first, then this page will show the dynamic problem-solution fields.</p>
            </div>
        </div>
    @else
    <form action="{{ route('admin.ecommerce.products.problem-solution.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($isProblemSolutionHub)
            <input type="hidden" name="return_to_hub" value="1">
        @endif

        <div class="card border-0 radius-12 mb-24">
            <div class="card-header bg-base border-bottom py-16 px-24 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="card-title mb-0">Problem Solution Section</h5>
                    <p class="text-secondary-light mb-0 mt-1">Edit the product answer section shown on the product detail page.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.ecommerce.products.index') }}" class="btn btn-outline-secondary btn-sm radius-8">Products</a>
                    <button type="submit" class="btn btn-primary-600 btn-sm radius-8">Save Section</button>
                </div>
            </div>

            <div class="card-body p-24">
                <div class="row g-4">
                    @if ($isProblemSolutionHub)
                        <div class="col-12">
                            <label class="form-label fw-bold">Select Product</label>
                            <select class="form-select" onchange="window.location.href='{{ route('admin.ecommerce.products.problem-solution.index') }}?product_id=' + this.value">
                                @foreach ($products as $productOption)
                                    <option value="{{ $productOption->id }}" {{ $productOption->id === $product->id ? 'selected' : '' }}>
                                        {{ $productOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-secondary-light">Choose which product detail page this section content should update.</small>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Main Heading</label>
                        <input type="text" name="ps_brand_title" class="form-control" value="{{ old('ps_brand_title', $product->ps_brand_title ?: $defaults['brand_title']) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Product Heading</label>
                        <input type="text" name="ps_product_title" class="form-control" value="{{ old('ps_product_title', $product->ps_product_title ?: $defaults['product_title']) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Small Tagline Items</label>
                        <div class="row g-3">
                            @for ($i = 0; $i < 3; $i++)
                                <div class="col-md-4">
                                    <input type="text" name="ps_tagline_items[]" class="form-control" value="{{ $taglineItems[$i] ?? '' }}" placeholder="e.g. Daily Nutrition">
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Center Product Image</label>
                        <input type="hidden" name="ps_center_image" value="{{ old('ps_center_image', $product->ps_center_image) }}">
                        <input type="file" name="ps_center_image_file" class="form-control" accept="image/*">
                        @php $centerPreview = $storageOrAsset($product->ps_center_image ?: $defaults['center_image']); @endphp
                        @if ($centerPreview)
                            <img src="{{ $centerPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 160px; height: 160px;" alt="">
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Bottom Left Image</label>
                        <input type="hidden" name="ps_shelf_left_image" value="{{ old('ps_shelf_left_image', $product->ps_shelf_left_image) }}">
                        <input type="file" name="ps_shelf_left_image_file" class="form-control" accept="image/*">
                        @php $leftShelfPreview = $storageOrAsset($product->ps_shelf_left_image ?: $defaults['shelf_left_image']); @endphp
                        @if ($leftShelfPreview)
                            <img src="{{ $leftShelfPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 140px; height: 120px;" alt="">
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Bottom Right Image</label>
                        <input type="hidden" name="ps_shelf_right_image" value="{{ old('ps_shelf_right_image', $product->ps_shelf_right_image) }}">
                        <input type="file" name="ps_shelf_right_image_file" class="form-control" accept="image/*">
                        @php $rightShelfPreview = $storageOrAsset($product->ps_shelf_right_image ?: $defaults['shelf_right_image']); @endphp
                        @if ($rightShelfPreview)
                            <img src="{{ $rightShelfPreview }}" class="mt-3 border radius-8 object-fit-contain bg-light" style="width: 140px; height: 120px;" alt="">
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 radius-12 h-100">
                    <div class="card-header bg-base border-bottom py-16 px-24 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Left Ingredient Cards</h6>
                            <small class="text-secondary-light">Power of Nature side</small>
                        </div>
                        <button type="button" class="btn btn-success-600 btn-sm radius-8" data-add-ps-row="left">Add Item</button>
                    </div>
                    <div class="card-body p-24">
                        <label class="form-label fw-bold">Left Label</label>
                        <textarea name="ps_left_label" rows="2" class="form-control mb-20">{{ old('ps_left_label', $product->ps_left_label ?: $defaults['left_label']) }}</textarea>

                        <div data-ps-list="left">
                            @foreach ($leftCards as $index => $card)
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
                <div class="card border-0 radius-12 h-100">
                    <div class="card-header bg-base border-bottom py-16 px-24 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Right Feature Cards</h6>
                            <small class="text-secondary-light">Daily Goodness side</small>
                        </div>
                        <button type="button" class="btn btn-success-600 btn-sm radius-8" data-add-ps-row="right">Add Item</button>
                    </div>
                    <div class="card-body p-24">
                        <label class="form-label fw-bold">Right Label</label>
                        <textarea name="ps_right_label" rows="2" class="form-control mb-20">{{ old('ps_right_label', $product->ps_right_label ?: $defaults['right_label']) }}</textarea>

                        <div data-ps-list="right">
                            @foreach ($rightCards as $index => $card)
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

        <div class="d-flex justify-content-end gap-2 mt-24">
            <a href="{{ $isProblemSolutionHub ? route('admin.ecommerce.products.problem-solution.index', ['product_id' => $product->id]) : route('admin.ecommerce.products.index') }}" class="btn btn-outline-secondary radius-8">Cancel</a>
            <button type="submit" class="btn btn-primary-600 radius-8">Save Section</button>
        </div>
    </form>

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function renameRow(row, side, index) {
                row.querySelector('[data-name="icon"]').name = `ps_${side}_cards[${index}][icon]`;
                row.querySelector('[data-name="title"]').name = `ps_${side}_cards[${index}][title]`;
                row.querySelector('[data-name="text"]').name = `ps_${side}_cards[${index}][text]`;
                row.querySelector('[data-name="image"]').name = `ps_${side}_card_images[${index}]`;
            }

            function reindex(side) {
                document.querySelectorAll(`[data-ps-list="${side}"] .ps-card-row`).forEach(function (row, index) {
                    renameRow(row, side, index);
                });
            }

            document.querySelectorAll('[data-add-ps-row]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const side = button.dataset.addPsRow;
                    const list = document.querySelector(`[data-ps-list="${side}"]`);
                    const row = document.getElementById('psRowTemplate').content.firstElementChild.cloneNode(true);
                    list.appendChild(row);
                    reindex(side);
                });
            });

            document.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-remove-ps-row]');
                if (!removeButton) return;

                const row = removeButton.closest('.ps-card-row');
                const list = row.closest('[data-ps-list]');
                const side = list.dataset.psList;
                row.remove();
                reindex(side);
            });
        });
    </script>
    @endif
@endsection
