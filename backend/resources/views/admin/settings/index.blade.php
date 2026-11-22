@extends('admin.layout')
@section('title', 'App settings')
@section('page_title', 'App settings')

@section('content')
    <div class="page-head">
        <div><h1>App settings</h1><p>Every value here is stored in MySQL and delivered to the Android app through the API.</p></div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @foreach ($settings as $group => $items)
            <div class="card" style="margin-bottom:16px">
                <div class="card-head"><h3>{{ ucfirst($group) }}</h3><span class="pill pill-blue">{{ $items->count() }} settings</span></div>
                <div class="card-pad">
                    <div class="grid grid-2" style="gap:16px">
                        @foreach ($items as $setting)
                            <div class="field">
                                <label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                                @if ($setting->type === 'bool')
                                    <label class="switch">
                                        <input type="hidden" name="bool_fields[]" value="{{ $setting->key }}">
                                        <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))>
                                        Enabled
                                    </label>
                                @elseif ($setting->type === 'text')
                                    <textarea name="settings[{{ $setting->key }}]">{{ $setting->value }}</textarea>
                                @elseif ($setting->type === 'file')
                                    <input type="file" name="{{ $setting->key }}" accept="image/*">
                                    <div class="hint">Current: {{ $setting->value ?: 'none' }}</div>
                                @elseif ($setting->type === 'int')
                                    <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                                @endif
                                <div class="hint mono">{{ $setting->key }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-bottom:30px">
            <button class="btn btn-primary" type="submit">Save all settings</button>
        </div>
    </form>
@endsection
