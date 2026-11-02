@php $resultImage = $result['image'] ?? ''; @endphp
<div class='transform-result-row border rounded-3 p-3 mb-3 bg-light'>
    <input type='hidden' name='transform_results[{{ $index }}][image]' data-field='image' value='{{ $resultImage }}'>
    <div class='row g-3 align-items-start'>
        <div class='col-md-2'>
            <div class='border rounded-3 bg-white d-flex align-items-center justify-content-center overflow-hidden' style='width:72px;height:72px'>
                @if ($transformImageUrl($resultImage))
                    <img src='{{ $transformImageUrl($resultImage) }}' class='w-100 h-100 object-fit-contain' alt=''>
                @else
                    <iconify-icon icon='lucide:image' class='text-secondary-light'></iconify-icon>
                @endif
            </div>
            <input type='file' data-field='image-file' class='form-control form-control-sm mt-2' accept='image/jpeg,image/png,image/webp'>
        </div>
        <div class='col-md-9'>
            <input type='text' name='transform_results[{{ $index }}][title]' data-field='title' class='form-control mb-2' maxlength='150' value='{{ $result['title'] ?? '' }}' placeholder='Result heading'>
            <textarea name='transform_results[{{ $index }}][description]' data-field='description' rows='2' class='form-control mb-2' maxlength='1000' placeholder='Result paragraph'>{{ $result['description'] ?? '' }}</textarea>
            <input type='text' name='transform_results[{{ $index }}][week]' data-field='week' class='form-control' maxlength='80' value='{{ $result['week'] ?? '' }}' placeholder='Optional: Visible by Week 3'>
        </div>
        <div class='col-md-1 text-end'>
            <button type='button' class='btn btn-outline-danger-600 btn-sm radius-8' data-remove-transform-result aria-label='Remove result'>
                <i class='fas fa-trash'></i>
            </button>
        </div>
    </div>
</div>
