@extends('hydro-raindrop::layouts.mfa')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">{{ __('Hydro MFA') }}</div>
                    <div class="card-body text-center">
                        <img src="{{ asset('vendor/hydro-raindrop/images/input-message.png') }}"
                             class="mb-3 w-50 img-fluid"
                             alt="{{ __('Enter Code in the Hydro App') }}">
                        <label>{{ __('Enter Security Code into the Hydro App') }}</label>
                        <form method="get">
                            @csrf
                            <p class="text-center font-weight-bold text-primary" style="font-size: 22px">
                                <code>{{ $message }}</code>
                            </p>

                            @isset($error)
                                <p class="text-danger">{{ $error }}</p>
                            @endisset
                            <input type="hidden" name="hydro_verify" value="1">

                            <button class="btn btn-primary" type="submit">
                                {{ __('Authenticate') }}
                            </button>
                        </form>
                        <div class="mt-3">
                            <a href="?hydro_cancel=1">{{ __('Cancel') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
