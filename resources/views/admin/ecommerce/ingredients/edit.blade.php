@extends('layouts.admin')
@php
    $title = 'Edit Ingredient';
    $subTitle = 'Ecommerce / Ingredients / Edit';
@endphp

@section('content')
    @include('admin.partials.messages')

    @include('admin.ecommerce.ingredients._form', [
        'formAction' => route('admin.ecommerce.ingredients.update', $ingredient),
        'submitLabel' => 'Update Ingredient',
        'ingredient' => $ingredient,
    ])
@endsection
