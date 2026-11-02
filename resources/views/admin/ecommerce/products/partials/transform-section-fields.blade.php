@php
    $transformProduct = $product ?? null;
    $transformDefaults = [
        ['image' => 'img/immune.png', 'title' => 'Stronger Immunity', 'description' => 'Kids fall sick less often. Parents report 60% fewer sick days in the first 3 months of consistent use.', 'week' => 'Visible by Week 3'],
        ['image' => 'img/check-height.png', 'title' => 'Height & Growth Spurt', 'description' => 'Ashwagandha + Zinc work synergistically to support natural growth hormone function and bone density.', 'week' => 'Visible by Week 8'],
        ['image' => 'img/energy-drink.png', 'title' => 'All-Day Energy', 'description' => 'No more afternoon crashes. Kids stay energetic and active through school, play, and evening activities.', 'week' => 'Visible by Week 2'],
        ['image' => 'img/mental-health.png', 'title' => 'Better Mood & Calm', 'description' => 'Adaptogenic Ashwagandha reduces cortisol — kids feel less stressed, sleep better, and wake up happier.', 'week' => 'Visible by Week 4'],
    ];
    $descriptionDefault = $transformProduct
        ? '90 days of ' . $transformProduct->name . ' — visible, measurable, life-changing results reported by thousands of parents.'
        : '90 days of consistent use — visible, measurable, life-changing results reported by thousands of parents.';
    $transformDescription = old('transform_description', $transformProduct?->transform_description ?: $descriptionDefault);
    $transformMainImage = old('transform_main_image', $transformProduct?->transform_main_image ?: 'img/tt1.jpeg');
    $savedTransformResults = $transformProduct?->transform_results;
    $transformResults = old('transform_results', is_array($savedTransformResults) ? $savedTransformResults : $transformDefaults);
    $transformImageUrl = function (?string $path): ?string {
        if (!$path) return null;
        return \Illuminate\Support\Str::startsWith($path, ['img/', 'assets/']) ? asset($path) : asset('storage/' . $path);
    };
@endphp

<div class='card border-0 radius-12 mb-24'>
    <div class='card-header bg-base border-bottom py-16 px-24'>
        <h5 class='card-title mb-0'>Real Results / Transformation Section</h5>
        <p class='text-secondary-light mb-0 mt-1'>Controls the paragraph, large left image, and result cards on this product detail page.</p>
    </div>
    <div class='card-body p-24'>
        <div class='row g-4'>
            <div class='col-lg-8'>
                <label class='form-label fw-bold'>Section Paragraph</label>
                <textarea name='transform_description' rows='3' class='form-control' maxlength='1000'>{{ $transformDescription }}</textarea>
                @error('transform_description')<span class='text-danger small d-block'>{{ $message }}</span>@enderror
            </div>
            <div class='col-lg-4'>
                <label class='form-label fw-bold'>Large Left Image</label>
                <input type='hidden' name='transform_main_image' value='{{ $transformMainImage }}'>
                <input type='file' name='transform_main_image_file' class='form-control' accept='image/jpeg,image/png,image/webp'>
                @if ($transformImageUrl($transformMainImage))
                    <img src='{{ $transformImageUrl($transformMainImage) }}' class='mt-3 border radius-8 object-fit-cover' style='width:150px;height:110px' alt='Current transformation image'>
                @endif
                @error('transform_main_image_file')<span class='text-danger small d-block'>{{ $message }}</span>@enderror
            </div>
        </div>

        <div class='border rounded-3 mt-4'>
            <div class='border-bottom py-14 px-16 d-flex align-items-center justify-content-between'>
                <div>
                    <h6 class='mb-0'>Right-side Result Cards</h6>
                    <small class='text-secondary-light'>Icon, heading, paragraph, and optional timing label.</small>
                </div>
                <button type='button' class='btn btn-success-600 btn-sm radius-8' data-add-transform-result>Add Result</button>
            </div>
            <div class='p-16' data-transform-results>
                @foreach ($transformResults as $index => $result)
                    @include('admin.ecommerce.products.partials.transform-result-row', compact('index', 'result', 'transformImageUrl'))
                @endforeach
            </div>
        </div>
    </div>
</div>

<template data-transform-result-template>
    <div class='transform-result-row border rounded-3 p-3 mb-3 bg-light'>
        <input type='hidden' data-field='image' value=''>
        <div class='row g-3 align-items-start'>
            <div class='col-md-2'>
                <div class='border rounded-3 bg-white d-flex align-items-center justify-content-center' style='width:72px;height:72px'><iconify-icon icon='lucide:image'></iconify-icon></div>
                <input type='file' data-field='image-file' class='form-control form-control-sm mt-2' accept='image/jpeg,image/png,image/webp'>
            </div>
            <div class='col-md-9'>
                <input type='text' data-field='title' class='form-control mb-2' maxlength='150' placeholder='Result heading'>
                <textarea data-field='description' rows='2' class='form-control mb-2' maxlength='1000' placeholder='Result paragraph'></textarea>
                <input type='text' data-field='week' class='form-control' maxlength='80' placeholder='Optional: Visible by Week 3'>
            </div>
            <div class='col-md-1 text-end'><button type='button' class='btn btn-outline-danger-600 btn-sm' data-remove-transform-result><i class='fas fa-trash'></i></button></div>
        </div>
    </div>
</template>

<script>
    (() => {
        const list = document.querySelector('[data-transform-results]');
        const addButton = document.querySelector('[data-add-transform-result]');
        const template = document.querySelector('[data-transform-result-template]');
        if (!list || !addButton || !template) return;

        const reindex = () => {
            list.querySelectorAll('.transform-result-row').forEach((row, index) => {
                row.querySelector('[data-field=image]').name = `transform_results[${index}][image]`;
                row.querySelector('[data-field=image-file]').name = `transform_result_images[${index}]`;
                row.querySelector('[data-field=title]').name = `transform_results[${index}][title]`;
                row.querySelector('[data-field=description]').name = `transform_results[${index}][description]`;
                row.querySelector('[data-field=week]').name = `transform_results[${index}][week]`;
            });
        };

        addButton.addEventListener('click', () => {
            list.appendChild(template.content.firstElementChild.cloneNode(true));
            reindex();
        });

        list.addEventListener('click', event => {
            const button = event.target.closest('[data-remove-transform-result]');
            if (!button) return;
            button.closest('.transform-result-row').remove();
            reindex();
        });

        reindex();
    })();
</script>
