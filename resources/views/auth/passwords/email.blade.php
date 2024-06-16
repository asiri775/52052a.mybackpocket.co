@extends('layouts.common_home')
@section('content')
<div class="container container-fixed-lg">
    <div class="row justify-content-center bravo-login-form-page bravo-login-page">
        <div class="col-md-5">
            <div class="">
                <h4 class="form-title">Reset Password</h4>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->has('email'))
                        <div class="alert alert-danger" role="alert">
                            No Account found associated with this email
                        </div>
                    @endif
                    <form class="form bravo-forms-register themeForm" method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="form-group">
                            <label class="formlabel fontsize13 robotoregular graytext font-weight-bold">Email Address
                                <span class=" required">*</span></label>
                                <input  type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                                <i class="input-icon field-icon icofont-mail"></i>
                                <span class="invalid-feedback error error-email"></span>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary form-submit">
                                Send Password Reset Link
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
@endsection
