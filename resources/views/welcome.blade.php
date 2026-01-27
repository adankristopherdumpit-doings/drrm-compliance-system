@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('DRRM Compliance System') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-4x text-primary mb-3"></i>
                        <h3>Welcome to DRRM Compliance System</h3>
                        <p class="text-muted">Disaster Risk Reduction and Management Compliance Tracking</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-sign-in-alt fa-2x text-success mb-2"></i>
                                    <h5>Already Registered?</h5>
                                    <a href="{{ route('login') }}" class="btn btn-success">Login</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                                    <h5>New User?</h5>
                                    <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
