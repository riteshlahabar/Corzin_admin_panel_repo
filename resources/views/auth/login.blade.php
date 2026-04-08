<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
</head>

<body>
<div class="container-xxl">
    <div class="row vh-100 d-flex justify-content-center">
        <div class="col-12 align-self-center">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 mx-auto">
                        <div class="card">
                            
                            <!-- Header -->
                           <div class="card-body p-0 auth-header-box rounded-top bg-white">
    <div class="text-center p-3">
        <img src="{{ asset('assets/images/company-logo.jpeg') }}" height="60">
        <h4 class="mt-3 mb-1 fw-semibold text-dark">Welcome Back</h4>
        <p class="text-dark mb-0">Sign in to continue</p>
    </div>
</div>

                            <!-- Form -->
                            <div class="card-body pt-0">
                                <form method="POST" action="{{ route('login') }}">
                                    @csrf

                                    <div class="form-group mb-2">
                                        <label class="form-label">Email</label>
                                        <input type="text" name="email" class="form-control" placeholder="Enter email">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Enter password">
                                    </div>

                                    <div class="form-group row mt-3">
                                        <div class="col-sm-6">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input">
                                                <label class="form-check-label">Remember me</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <a href="#" class="text-muted">Forgot password?</a>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <button class="btn" type="submit" style="background:#448100; color:#fff; border:none;">
                                            Log In
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-3">
                                    <p class="text-muted">
                                        Don't have an account?
                                        <a href="#" class="text-primary">Register</a>
                                    </p>
                                </div>

                            </div><!-- card-body -->

                        </div><!-- card -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>