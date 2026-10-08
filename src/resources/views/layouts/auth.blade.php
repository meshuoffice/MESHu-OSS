<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ログイン') - MESHu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Meiryo, sans-serif;
        }
        .auth-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            background: #ffffff;
            overflow: hidden;
            width: 100%;
            max-width: 440px;
        }
        .auth-header {
            background: #1e3a8a;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .auth-header.admin {
            background: #1f2937;
        }
        .auth-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.05em;
        }
        .auth-body {
            padding: 32px;
        }
        .form-floating label {
            color: #64748b;
        }
        .btn-primary-custom {
            background-color: #2563eb;
            border-color: #2563eb;
            padding: 10px 0;
            font-weight: 600;
        }
        .btn-primary-custom:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        .btn-dark-custom {
            background-color: #111827;
            border-color: #111827;
            padding: 10px 0;
            font-weight: 600;
        }
        .btn-dark-custom:hover {
            background-color: #1f2937;
            border-color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                <div class="auth-card">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
