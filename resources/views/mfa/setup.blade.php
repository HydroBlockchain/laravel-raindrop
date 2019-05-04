@extends('hydro-raindrop::layouts.mfa')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-8 col-md-4">
                <div class="card">
                    <div class="card-header">{{ __('Hydro MFA') }}</div>
                    <div class="card-body text-center">
                        <img src="{{ asset('vendor/hydro-raindrop/images/input-hydro-id.png') }}"
                             class="mb-3 w-50 img-fluid"
                             alt="{{ 'Enter your HydroID' }}">
                            <p class="text-primary"><strong>{{ __('Enter your personal HydroID') }}</strong></p>
                            <p>
                                Download the <span class="text-bold">free</span> Hydro security and identity app for
                                <a href="https://itunes.apple.com/app/id1406519814">iOS</a> {{ __('or') }}
                                <a href="https://play.google.com/store/apps/details?id=com.hydrogenplatform.hydro">Android</a> and create your secure HydroID.
                            </p>
                        <p>{{ __('Enter your personal HydroID below to continue.') }}</p>
                        <form method="get">
                            <div class="form-row">
                                <div class="col-xs-12 col-sm-8 col-md-6">
                                <input type="text"
                                       name="hydro_id"
                                       class="form-control"
                                       value=""
                                       placeholder="{{ __('Enter your HydroID') }}"
                                       autocomplete="off"
                                       autofocus>
                                </div>
                                <div class="col-xs-12 col-sm-4 col-md-6">
                                    <button class="btn btn-block btn-primary" type="submit">{{ __('Submit') }}</button>
                                </div>
                                @if($mfaMethod !== 'enforced')
                                    <div class="col-xs-12 mt-3 text-center">
                                        <a href="?hydro_skip=1">{{ __('Skip') }}</a>
                                    </div>
                                @else
                                    <div class="col-xs-12 mt-3 text-center">
                                        <a href="?hydro_cancel=1">{{ __('Cancel') }}</a>
                                    </div>
                                @endif
                            </div>
                                @isset($error)
                                    <p class="text-danger mt-3">{{ $error }}</p>
                                @endisset
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
