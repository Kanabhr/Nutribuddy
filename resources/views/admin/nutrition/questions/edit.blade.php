@extends('layouts.admin')

@php
  $title = 'Edit Nutrition Question';
  $subTitle = 'Diet Chart / Nutrition Questions / Edit';
@endphp

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">Edit Question</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.nutrition.questions.update', $question->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section', $question->section) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Question <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control" value="{{ old('question', $question->question) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Answer Type <span class="text-danger">*</span></label>
                    <input type="text" name="answer_type" class="form-control" value="{{ old('answer_type', $question->answer_type) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Options / Scale</label>
                    <input type="text" name="options_scale" class="form-control" value="{{ old('options_scale', str_replace('Few times a yr', 'Few times a year', (string) $question->options_scale)) }}" placeholder="Separate choice options with /">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Condition (Only ask if)</label>
                    <input type="text" name="only_ask_if" class="form-control" value="{{ old('only_ask_if', $question->only_ask_if) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $question->sort_order) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Clinical Reason</label>
                    <input type="text" name="clinical_reason" class="form-control" value="{{ old('clinical_reason', $question->clinical_reason) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Shapes Chart (Effect)</label>
                    <input type="text" name="chart_effect" class="form-control" value="{{ old('chart_effect', $question->chart_effect) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="hidden" name="is_optional" value="0">
                        <input type="checkbox" name="is_optional" id="is_optional" class="form-check-input" value="1" @checked(old('is_optional', $question->is_optional))>
                        <label class="form-check-label" for="is_optional">Optional question</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update Question</button>
                <a href="{{ route('admin.nutrition.questions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
  </div>
@endsection
