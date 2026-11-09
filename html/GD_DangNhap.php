<?php
// BỔ SUNG: trang đăng nhập sử dụng dữ liệu trong `Tai Khoan`.
require_once dirname(__FILE__) . '/../php/Control/DangNhapController.php';
$vm = new DangNhapController();
$login = $vm->xuLy();
function hDangNhap($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | EIGHT Mart</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center;
            justify-content: center; font-family: Arial, Helvetica, sans-serif; color: #233347;
            background: radial-gradient(circle at 15% 15%, #e6f3f5 0, transparent 32%),
                        linear-gradient(130deg, #f3f7fb, #edf3f7); padding: 26px 16px; }
        .layout { width: 100%; max-width: 870px; min-height: 492px; display: flex;
            border-radius: 18px; overflow: hidden; background: #fff;
            box-shadow: 0 18px 60px rgba(27, 49, 71, .12); }
        .brand { flex: 1; background: linear-gradient(155deg,#1c3047,#233e58 65%,#1a5761);
            padding: 50px 44px; color: white; display: flex; flex-direction: column; justify-content: space-between; }
        .badge { display: inline-flex; width: 48px; height: 48px; border-radius: 14px;
            align-items: center; justify-content: center; background: #27c3ab; color: #18374b;
            font-size: 27px; font-weight: bold; }
        .brand h1 { letter-spacing: 1.5px; font-size: 36px; margin: 18px 0 8px; }
        .brand p { color: #bdd4e3; line-height: 1.65; font-size: 15px; margin: 0; }
        .brand-foot { border-top: 1px solid rgba(255,255,255,.15);
            padding-top: 18px; font-size: 12px; color: #b5cbdc; }
        .panel { width: 48%; padding: 65px 47px; display: flex; flex-direction: column;
            justify-content: center; }
        .eyebrow { color: #0b8e88; font-size: 12px; font-weight: bold; letter-spacing: 1px; margin-bottom: 12px; }
        h2 { font-size: 27px; letter-spacing: -.5px; margin: 0 0 8px; }
        .subtitle { margin: 0 0 32px; line-height: 1.6; color: #718193; font-size: 13px; }
        label { display: block; font-weight: bold; font-size: 13px; margin-bottom: 9px; }
        .field { margin-bottom: 20px; }
        input[type="text"], input[type="password"] { display: block; width: 100%; padding: 12px 13px;
            font-size: 14px; height: 47px; color: #233347; border: 1px solid #ced8e0;
            border-radius: 8px; outline: none; background: #fff; }
        input:focus { border-color: #139e97; box-shadow: 0 0 0 3px rgba(19,158,151,.12); }
        .password-box { position: relative; }
        .password-box input { padding-right: 65px; }
        .toggle { position: absolute; right: 8px; top: 8px; background: transparent; border: 0;
            font-size: 12px; color: #277875; cursor: pointer; padding: 8px; }
        .submit { margin-top: 3px; width: 100%; height: 47px; border: 0; border-radius: 8px;
            background: #169b91; color: #fff; cursor: pointer; font-weight: bold; font-size: 15px; }
        .submit:hover { background: #0f827a; }
        .error { color: #a82724; background: #fff0ef; border: 1px solid #ffcccc;
            border-radius: 8px; padding: 10px 12px; margin: -12px 0 20px;
            font-size: 13px; line-height: 1.5; }
        /* UC01: thông báo lỗi tại đúng trường nhập */
        .input-error { border-color: #c0392b !important; }
        .field-error { font-size: 12px; color: #b32624; line-height: 1.4; margin-top: 6px; }
        .hint { margin-top: 23px; color: #82909f; font-size: 12px; line-height: 1.6; text-align: center; }
        @media (max-width: 700px) { .layout { display: block; max-width: 420px; }
            .brand { padding: 25px 30px; } .brand h1 { font-size: 27px; margin-top: 10px; }
            .brand-foot { display: none; } .panel { width: 100%; padding: 36px 30px; } }
    </style>
</head>
<body>
    <main class="layout">
        <section class="brand">
            <div>
                <div class="badge">8</div>
                <h1>EIGHT MART</h1>
                <p>Hệ thống quản lý cửa hàng tiện lợi.<br>Quản lý hoạt động tập trung, nhanh chóng và rõ ràng.</p>
            </div>
            <div class="brand-foot">TRANG QUẢN TRỊ NỘI BỘ &bull; EIGHT MART</div>
        </section>
        <section class="panel">
            <span class="eyebrow">CHÀO MỪNG TRỞ LẠI</span>
            <h2>Đăng nhập</h2>
            <p class="subtitle">Nhập thông tin tài khoản đã được cấp để tiếp tục.</p>
            <?php if ($login['loi'] !== ''): ?>
                <div class="error" role="alert"><?php echo hDangNhap($login['loi']); ?></div>
            <?php endif; ?>
            <form method="post" action="GD_DangNhap.php" id="formDangNhap">
                <input type="hidden" name="csrf_token" value="<?php echo hDangNhap($login['csrf']); ?>">
                <div class="field">
                    <label for="ten_dang_nhap">Tên đăng nhập</label>
                    <input type="text" id="ten_dang_nhap" name="ten_dang_nhap"
                        value="<?php echo hDangNhap($login['ten_dang_nhap']); ?>"
                        placeholder="Tên đăng nhập hoặc số CCCD" maxlength="60"
                        autocomplete="username" required autofocus
                        <?php if (isset($login['errors']['ten_dang_nhap'])): ?>class="input-error" aria-invalid="true" aria-describedby="error_username"<?php endif; ?>>
                    <?php if (isset($login['errors']['ten_dang_nhap'])): ?>
                        <div class="field-error" id="error_username"><?php echo hDangNhap($login['errors']['ten_dang_nhap']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="mat_khau">Mật khẩu</label>
                    <div class="password-box">
                        <input type="password" id="mat_khau" name="mat_khau" placeholder="Nhập mật khẩu"
                            autocomplete="current-password" required
                            <?php if (isset($login['errors']['mat_khau'])): ?>class="input-error" aria-invalid="true" aria-describedby="error_password"<?php endif; ?>>
                        <button type="button" id="hienMatKhau" class="toggle" aria-label="Hiện mật khẩu">Hiện</button>
                    </div>
                    <?php if (isset($login['errors']['mat_khau'])): ?>
                        <div class="field-error" id="error_password"><?php echo hDangNhap($login['errors']['mat_khau']); ?></div>
                    <?php endif; ?>
                </div>
                <button class="submit" type="submit">Đăng nhập</button>
            </form>
            <p class="hint">Tài khoản được quản lý và xác thực bằng cơ sở dữ liệu EightMart.</p>
        </section>
    </main>
    <script>
        (function() {
            var b = document.getElementById('hienMatKhau');
            var f = document.getElementById('mat_khau');
            b.onclick = function() {
                var hien = f.type === 'password';
                f.type = hien ? 'text' : 'password';
                b.textContent = hien ? 'Ẩn' : 'Hiện';
                b.setAttribute('aria-label', hien ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            };
        })();
    </script>
</body>
</html>
