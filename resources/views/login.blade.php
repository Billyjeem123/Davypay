<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – DAVY PAY App | Fintech & Digital Payment Platform</title>
    <meta name="description" content="Securely log in to your DAVY PAY App admin dashboard. Manage user transactions, airtime recharges, payments, and platform settings.">
    <meta name="keywords" content="DAVY PAY App, fintech Nigeria, send money, receive payments, airtime recharge, wallet system, digital transactions, payment platform, mobile finance">
    <meta name="author" content="DAVY PAY App">

    <meta property="og:title" content="Admin Login – DAVY PAY App">
    <meta property="og:description" content="Securely log in to your DAVY PAY App admin dashboard">
    <meta property="og:image" content="/logo.png">
    <meta property="og:url" content="">
    <meta property="og:type" content="website">
    <link rel="shortcut icon" href="/logo.png" type="image/png">

    <!-- Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            animation: rotate 10s linear infinite;
            z-index: 1;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            display: block;
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo img {
            max-width: 120px;
            height: auto;
        }

        .admin-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.7;
            transform: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }

        .password-field {
            position: relative;
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
<div class="login-container">
    <div class="login-content">
        <a href="#" class="logo">
            <img src="/logo.png" alt="Davy App Logo">
        </a>

        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i>
            Admin Dashboard Access
        </div>

        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
                <input type="email"
                       class="form-control"
                       id="email"
                       name="email"
                       placeholder="admin@davypay.com"
                       required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <div class="password-field">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100" id="loginBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login to Dashboard
            </button>
        </form>

        <div class="login-footer">
            <p class="mb-0">
                <i class="fas fa-lock me-1"></i>
                Secure admin access for <strong>DAVY PAY App</strong>
            </p>
            <small class="text-muted">
                Having trouble? <a href="#" id="supportLink">Contact Support</a>
            </small>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Password toggle functionality
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Login form submission
    // Login form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        const email = $('#email').val().trim();
        const password = $('#password').val();
        const btn = $('#loginBtn');
        const originalText = btn.html();

        // Basic validation
        if (!email || !password) {
            toastr.error('Please fill in all fields.');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            toastr.error('Please enter a valid email address.');
            return;
        }

        // Set CSRF token in request headers for Laravel
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            xhrFields: {
                withCredentials: true // ensures cookies like `laravel_session` are sent along
            }
        });

        // Disable button and show loading
        btn.prop('disabled', true).html('<div class="loading-spinner d-inline-block"></div>Authenticating...');

        // AJAX login request
        $.ajax({
            url: "/admin/login",
            method: 'POST',
            data: {
                email: email,
                password: password
                // _token no longer needed here if you added it to headers above
            },
            success: function(response) {
                if (response.status === true || response.success === true) {
                    toastr.success(response.message || 'Login successful! Redirecting...');
                    setTimeout(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '/admin/home';
                        }
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Login failed. Please try again.');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON;
                if (xhr.status === 401) {
                    toastr.error('Invalid credentials. Please check your email and password.');
                } else if (xhr.status === 422) {
                    if (res?.errors) {
                        Object.values(res.errors).forEach(err => {
                            if (Array.isArray(err)) {
                                err.forEach(msg => toastr.error(msg));
                            } else {
                                toastr.error(err);
                            }
                        });
                    } else {
                        toastr.error(res?.message || 'Validation failed.');
                    }
                } else if (xhr.status === 429) {
                    toastr.error('Too many login attempts. Please try again later.');
                } else if (xhr.status >= 500) {
                    toastr.error('Server error. Please try again later.');
                } else {
                    toastr.error(res?.message || 'An error occurred. Please try again.');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });


    // Support link click
    $('#supportLink').on('click', function(e) {
        e.preventDefault();
        toastr.info('Please contact support at support@DAVY PAYapp.com for assistance.');
    });

    // Add some interactive effects
    $(document).ready(function() {
        // Focus effects
        $('.form-control').on('focus', function() {
            $(this).parent().addClass('focused');
        }).on('blur', function() {
            $(this).parent().removeClass('focused');
        });

        // Welcome message
        setTimeout(() => {
            toastr.info('Welcome to DAVY PAY  Admin Panel', 'Access Required');
        }, 1000);
    });
</script>
</body>

</html>
