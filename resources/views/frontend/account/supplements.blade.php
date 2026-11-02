@extends('layouts.user-panel')
@section('title', 'Supplements')
@section('panel-content')
<div class="nbPage">
  <div class="nbPageHead"><h1 class="nbPageTitle">Supplement Schedule</h1><p class="nbPageMeta">{{ $child?->name ?? 'Your child' }} · Products selected for this diet plan</p></div>
  <div class="nbCardGrid">
    @forelse($products as $product)
      <article class="nbCard">
        @if($product->primaryImage?->image_path)<img src="{{ asset('storage/' . $product->primaryImage->image_path) }}" alt="{{ $product->name }}" style="width:100%;max-height:180px;object-fit:contain;margin-bottom:16px">@endif
        <div class="nbCardName">{{ $product->name }}</div><div class="nbCardWhen">{{ $product->routine ?: 'Use as directed by your nutritionist' }}</div><div class="nbCardDivider"></div>
        <p class="nbCardDesc">{{ $product->short_description ?: $product->description }}</p>
        @if($product->dosage)<div class="nbStatRow"><div class="nbStatBox"><div class="nbStatVal">{{ $product->dosage }}</div><div class="nbStatLabel">Dosage</div></div></div>@endif
        <a class="nbReorderBtn" href="{{ route('product.show', $product->slug) }}">View product <span class="nbReorderArrow">→</span></a>
      </article>
    @empty
      <div class="nbAlertBox"><div class="nbAlertHead">No supplements selected</div><div class="nbAlertBody">Your nutritionist can add products while reviewing your diet chart.</div></div>
    @endforelse
  </div>
</div>
@endsection
