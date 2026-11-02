@extends('layouts.admin')

@php
  $title = 'Add Nutrition Question';
  $subTitle = 'Diet Chart / Nutrition Questions / Add';
@endphp

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">Add Question</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.nutrition.questions.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Model <span class="text-danger">*</span></label>
                    <select name="model" class="form-select" required>
                        <option value="1 Basic" {{ old('model', request('model')) == '1 Basic' ? 'selected' : '' }}>1 Basic</option>
                        <option value="2 Plus" {{ old('model', request('model')) == '2 Plus' ? 'selected' : '' }}>2 Plus</option>
                        <option value="3 Pro" {{ old('model', request('model')) == '3 Pro' ? 'selected' : '' }}>3 Pro</option>
                        <option value="4 Premium" {{ old('model', request('model')) == '4 Premium' ? 'selected' : '' }}>4 Premium</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section') }}" placeholder="e.g. A · Child & Growth">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Question <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control" value="{{ old('question') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Answer Type <span class="text-danger">*</span></label>
                    <input type="text" name="answer_type" class="form-control" value="{{ old('answer_type') }}" placeholder="Text, Choice, Multi, etc." required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Options / Scale</label>
                    <input type="text" name="options_scale" class="form-control" value="{{ old('options_scale') }}" placeholder="e.g. Boy / Girl">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Condition (Only ask if)</label>
                    <input type="text" name="only_ask_if" class="form-control" value="{{ old('only_ask_if', 'always') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Clinical Reason</label>
                    <input type="text" name="clinical_reason" class="form-control" value="{{ old('clinical_reason') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Shapes Chart (Effect)</label>
                    <input type="text" name="chart_effect" class="form-control" value="{{ old('chart_effect') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 1) }}" required>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Question</button>
                <a href="{{ route('admin.nutrition.questions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
  </div>
@endsection
