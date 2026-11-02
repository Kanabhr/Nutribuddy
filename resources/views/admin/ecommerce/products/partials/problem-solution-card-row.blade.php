@php
    $card = is_array($card) ? $card : [];
    $icon = $card['icon'] ?? '';
    $preview = $storageOrAsset($icon);
@endphp

<div class="ps-card-row border rounded-3 p-3 mb-3 bg-light">
    <input type="hidden" name="ps_{{ $side }}_cards[{{ $index }}][icon]" data-name="icon" value="{{ $icon }}">
    <div class="d-flex align-items-start gap-3">
        <div class="flex-shrink-0">
            <div class="border rounded-3 bg-white d-flex align-items-center justify-content-center overflow-hidden" style="width:72px;height:72px;">
                @if ($preview)
                    <img src="{{ $preview }}" alt="" class="w-100 h-100 object-fit-contain">
                @else
                    <iconify-icon icon="lucide:image" class="text-secondary-light"></iconify-icon>
                @endif
            </div>
        </div>
        <div class="flex-grow-1">
            <input type="text" name="ps_{{ $side }}_cards[{{ $index }}][title]" data-name="title" class="form-control mb-2" value="{{ $card['title'] ?? '' }}" placeholder="Title">
            <textarea name="ps_{{ $side }}_cards[{{ $index }}][text]" data-name="text" rows="2" class="form-control mb-2" placeholder="Description">{{ $card['text'] ?? '' }}</textarea>
            <input type="file" name="ps_{{ $side }}_card_images[{{ $index }}]" data-name="image" class="form-control" accept="image/*">
        </div>
        <button type="button" class="btn btn-outline-danger-600 btn-sm radius-8" data-remove-ps-card>
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>
