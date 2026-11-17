@extends('layouts.admin')

@section('title', __('settings.Update_Settings'))
@section('content-header', __('settings.Update_Settings'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('settings.store') }}" method="post">
            @csrf

            <div class="form-group">
                <label for="app_name">{{ __('settings.app_name') }}</label>
                <input type="text" name="app_name" class="form-control @error('app_name') is-invalid @enderror" id="app_name" placeholder="{{ __('settings.App_name') }}" value="{{ old('app_name', config('settings.app_name')) }}">
                @error('app_name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="app_description">{{ __('settings.app_description') }}</label>
                <textarea name="app_description" class="form-control @error('app_description') is-invalid @enderror" id="app_description" placeholder="{{ __('settings.app_description') }}">{{ old('app_description', config('settings.app_description')) }}</textarea>
                @error('app_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="currency_preset">Currency Preset</label>
                        <select id="currency_preset" class="form-control" onchange="applyCurrencyPreset(this.value)">
                            <option value="">-- Choose preset or type below --</option>
                            <option value="$">USD / CAD / AUD ($)</option>
                            <option value="€">EUR (€)</option>
                            <option value="£">GBP (£)</option>
                            <option value="¥">CNY / JPY (¥)</option>
                            <option value="៛">KHR - Cambodian Riel (៛)</option>
                            <option value="₹">INR - Indian Rupee (₹)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="currency_symbol">{{ __('settings.Currency_symbol') }}</label>
                        <input type="text" name="currency_symbol" class="form-control @error('currency_symbol') is-invalid @enderror" id="currency_symbol" placeholder="{{ __('settings.Currency_symbol') }}" value="{{ old('currency_symbol', config('settings.currency_symbol', '$')) }}">
                        @error('currency_symbol')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="currency_placement">Symbol Position</label>
                        <select name="currency_placement" id="currency_placement" class="form-control">
                            <option value="left" {{ old('currency_placement', config('settings.currency_placement', 'left')) == 'left' ? 'selected' : '' }}>Left ($ 10.00)</option>
                            <option value="right" {{ old('currency_placement', config('settings.currency_placement', 'left')) == 'right' ? 'selected' : '' }}>Right (10.00 $ / 10.00 ៛)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="warning_quantity">{{ __('settings.warning_quantity') }}</label>
                <input type="text" name="warning_quantity" class="form-control @error('warning_quantity') is-invalid @enderror" id="warning_quantity" placeholder="{{ __('settings.warning_quantity') }}" value="{{ old('warning_quantity', config('settings.warning_quantity')) }}">
                @error('warning_quantity')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('settings.Change_Setting') }}</button>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    function applyCurrencyPreset(symbol) {
        if (!symbol) return;
        document.getElementById('currency_symbol').value = symbol;
        if (symbol === '៛') {
            document.getElementById('currency_placement').value = 'right';
        } else {
            document.getElementById('currency_placement').value = 'left';
        }
    }
</script>
@endsection
