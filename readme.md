# EIGHT Mart — tài liệu dự án

Tài liệu này mô tả mã nguồn hiện có trong thư mục dự án, các luồng chạy chính, hàm/lớp, cấu trúc dữ liệu và những điểm cần lưu ý khi cài đặt. Đây là tài liệu theo trạng thái mã nguồn đã kiểm tra; các màn hình `.html` mẫu được phân biệt với các trang PHP có xử lý thật.

## 1. Tổng quan

EIGHT Mart là ứng dụng web quản lý cửa hàng, viết bằng PHP thuần, HTML/CSS/JavaScript và MySQL. Các nhóm chức năng đã có xử lý phía máy chủ:

- Đăng nhập, phiên làm việc và đăng xuất.
- Trang tổng quan.
- Quản lý nhân viên và tài khoản nhân viên.
- Quản lý nhà cung cấp.
- Tạo phiếu đề nghị đặt hàng theo nhà cung cấp.
- Xem và xử lý yêu cầu hoàn trả.
- Bán hàng, lập hóa đơn, thanh toán tiền mặt/chuyển khoản.
- Xem báo cáo nhân viên, ca làm việc, số hóa đơn và doanh thu.
- In hóa đơn.

Một số trang quản lý khuyến mãi, duyệt đặt hàng và các màn hình `.html` khác chỉ là giao diện mẫu, chưa phải luồng nghiệp vụ hoàn chỉnh.

## 2. Công nghệ và cấu hình hiện tại

- PHP thuần, không thấy Composer hoặc framework trong project.
- MySQLi là lớp truy cập cơ sở dữ liệu trong mã PHP.
- Cấu hình dùng để phát triển trong [db.php](db.php): `localhost:3306`, user `root`, mật khẩu rỗng, database `eightmart`, charset `utf8`.
- Môi trường WAMP đã được xác định khi kiểm tra: PHP CLI 5.2.6 và MySQL 5.0.51b. Đây là phiên bản rất cũ; kiểm tra lại cấu hình máy triển khai trước khi dùng.
- Giao diện dùng HTML, CSS và JavaScript trực tiếp trong một số trang PHP; không có quy trình build frontend.
- Schema dùng `TIMESTAMP`; trên MySQL đời cũ, kiểu này có giới hạn phạm vi ngày (đặc biệt mốc kết thúc khoảng năm 2038).
- MySQL 5.0 hiểu `NVARCHAR` như kiểu chuỗi tương thích với bộ ký tự quốc gia của MySQL (thường ánh xạ sang `VARCHAR`/UTF-8), không giống ngữ nghĩa `NVARCHAR` của SQL Server. Kết nối PHP và file SQL vẫn phải thống nhất UTF-8.

## 3. Cách khởi chạy trên WAMP

1. Đặt project bên dưới `C:\wamp\www`, ví dụ:
   `C:\wamp\www\PTUD\PhatTienUngDung_NhomEight`.
2. Bật Apache và MySQL trong WAMP.
3. Kiểm tra cấu hình trong `db.php`; mặc định ứng dụng kết nối database `eightmart`.
4. Tạo database và import **một** schema phù hợp. Mã nguồn hiện tại dùng các bảng PascalCase theo [schema.sql](schema.sql). Không import lặp file này vào database đã có bảng/dữ liệu: phần `CREATE TABLE` và `INSERT` dữ liệu mẫu không được viết để chạy lặp an toàn.
5. Mở `http://localhost/PTUD/PhatTienUngDung_NhomEight/`. [index.php](index.php) chuyển người dùng đã đăng nhập tới trang tổng quan, nếu chưa đăng nhập thì chuyển tới trang đăng nhập.
6. Có thể kiểm tra kết nối tại `http://localhost/PTUD/PhatTienUngDung_NhomEight/php/kiemtra_ketnoi.php` (điều chỉnh URL theo vị trí project thực tế).

### Cảnh báo về SQL và đăng nhập mẫu

- [schema.sql](schema.sql) là schema được các truy vấn PHP hiện tại mong đợi: tên bảng/cột như `NhanVien`, `TaiKhoan`, `HoaDon`, `SanPham`.
- [eightmart.sql](eightmart.sql) là dump cũ, dùng tên bảng/cột theo kiểu snake_case như `nhan_vien`, `don_hang`, `tai khoan`; không tương đương schema hiện tại. Không chọn file này chỉ vì tên database trùng nhau.
- Dữ liệu tài khoản mẫu trong `schema.sql` được lưu bằng `MD5(...)`; `BaoMat::kiemTraMatKhau` hiện nhận diện hash MD5 32 ký tự và so sánh với MD5 của mật khẩu nhập. Tài khoản `dung.pham` còn có trạng thái ngừng hoạt động.
- Dữ liệu tiếng Việt đã bị chuyển thành dấu `?` hoặc mojibake trong database thì đổi kiểu cột sang `NVARCHAR` không khôi phục được ký tự gốc. Cần sửa dữ liệu nguồn và nhập lại với kết nối/file UTF-8.
- `db.php` đang đặt user `root` và mật khẩu rỗng để chạy local. Không dùng cấu hình này cho máy chủ công khai.

## 4. Luồng ứng dụng

### Đăng nhập và phân quyền

1. Trình duyệt mở `index.php`.
2. [XacThuc::layNguoiDung](php/XacThuc.php) kiểm tra phiên, thời gian hết hạn và trạng thái tài khoản/nhân viên trong database.
3. Người chưa đăng nhập được chuyển tới `html/GD_DangNhap.php`.
4. [DangNhapController::xuLy](php/Control/dangNhapController.php) kiểm tra đầu vào và CSRF token, sau đó gọi `TaiKhoanRep::xacThuc`.
5. Khi thành công, `XacThuc::dangNhap` lưu thông tin phiên và trang chuyển về dashboard.
6. Đăng xuất là POST có token, xử lý trong `html/logout.php`.

Vai trò `QuanLy`/`ChuDoanhNghiep` được chuẩn hóa thành chủ doanh nghiệp; `ThuNgan`, `ThuKho` và các vai trò còn lại được chuẩn hóa thành nhân viên. Một số controller áp dụng quyền chặt hơn các controller khác; xem phần lưu ý chức năng.

### Bán hàng và thanh toán

1. `html/GD_QuanLyBanHang.php` tải danh sách sản phẩm đang hoạt động từ MySQL và giữ giỏ hàng tạm thời trong JavaScript.
2. JavaScript gọi `ThanhToan/xulythanhtoan.php?action=tim_sp` để lấy thông tin sản phẩm; giá chính thức luôn được đọc lại từ database khi tạo hóa đơn.
3. Tiền mặt: kiểm tra kho và tiền khách đưa; tạo hóa đơn, chi tiết, thanh toán; trừ kho; đánh dấu hóa đơn đã thanh toán trong transaction.
4. Chuyển khoản: tạo thanh toán chờ xác nhận và trả về thông tin nội dung/QR; khi nhân viên xác nhận thì kiểm tra hạn QR và tồn kho lần nữa, sau đó mới trừ kho và hoàn tất hóa đơn.
5. Hủy hoặc hết hạn chuyển khoản sẽ đánh dấu thanh toán thất bại và hóa đơn bị hủy.
6. `ThanhToan/hoadon.php?id=...` là trang xem/in hóa đơn.

### Phiếu đặt hàng

`GD_LapPhieuDatHang.php` lấy sản phẩm dưới mức tồn tối thiểu và nhà cung cấp liên kết. Controller gom sản phẩm theo nhà cung cấp rồi repository tạo từng phiếu đề nghị cùng các dòng chi tiết. Mỗi phiếu theo nhà cung cấp được lưu trong transaction riêng. Trang duyệt đặt hàng hiện chỉ là HTML mẫu; chưa thấy endpoint PHP tương ứng trong project.

### Hoàn trả

`GD_XuLyHoanTra.php` hiển thị yêu cầu hoàn trả và gửi POST tới `php/Control/xuLyHoanTra.php`. Controller kiểm tra trạng thái chờ xử lý, sau đó repository cập nhật trạng thái, người xử lý, thời điểm và lý do từ chối nếu có. Mã hiện tại không cho thấy việc tự hoàn tiền hoặc nhập lại hàng trong luồng này.

### Báo cáo

Trang báo cáo liệt kê nhân viên/ca làm việc. Trang chi tiết gọi `BaoCao` để đếm hóa đơn đã thanh toán/hoàn tất và tổng doanh thu. Hóa đơn được tính khi có bản ghi thanh toán thành công/đã thanh toán; truy vấn dùng `EXISTS` để tránh tính lặp hóa đơn khi có nhiều thanh toán.

## 5. Cấu trúc thư mục và file

### Thư mục gốc

| File | Vai trò |
|---|---|
| `index.php` | Điểm vào ứng dụng; định tuyến theo trạng thái đăng nhập. |
| `db.php` | Cấu hình và các helper MySQLi dùng chung. |
| `schema.sql` | Schema hiện tại theo tên bảng/cột mà các trang PHP sử dụng, kèm dữ liệu mẫu. |
| `eightmart.sql` | Dump schema/dữ liệu cũ theo tên bảng snake_case; không phải schema tương thích trực tiếp với code hiện tại. |
| `readme.md` | Tài liệu kiến trúc, luồng nghiệp vụ, file, hàm và lưu ý vận hành của project. |

### `php/`

#### File dùng chung

| File | Vai trò |
|---|---|
| `BaoMat.php` | So sánh chuỗi, sinh token ngẫu nhiên, băm và xác minh mật khẩu. |
| `XacThuc.php` | Quản lý session, xác minh tài khoản mỗi request, đăng nhập/đăng xuất và bảo vệ trang. |
| `kiemtra_ketnoi.php` | Trang chẩn đoán kết nối MySQL và đếm nhân viên. |

#### `php/Control/`

| File | Vai trò |
|---|---|
| `dangNhapController.php` | Kiểm tra biểu mẫu, CSRF, xác thực và phản hồi cho trang đăng nhập. |
| `nhanVienController.php` | CRUD nhân viên, kiểm tra quyền, tạo tài khoản và xử lý form. |
| `nhaCungCapController.php` | CRUD nhà cung cấp, kiểm tra dữ liệu và tham chiếu. |
| `lapPhieuDatHangControl.php` | Điều phối việc lập phiếu đề nghị đặt hàng theo nhà cung cấp. |
| `hoanTraController.php` | Nghiệp vụ chấp nhận/từ chối yêu cầu hoàn trả. |
| `xuLyHoanTra.php` | Endpoint POST nhận thao tác hoàn trả và chuyển kết quả về trang giao diện. |
| `baoCao.php` | Truy vấn danh sách và số liệu báo cáo nhân viên/ca. |

#### `php/Entity/`

| File | Vai trò |
|---|---|
| `NhanVien.php` | Mô hình nhân viên, chuẩn hóa/kiểm tra dữ liệu và tạo mật khẩu theo ngày sinh. |
| `NhaCungCap.php` | Mô hình nhà cung cấp và validation. |
| `PhieuDeNghiDatHang.php` | Đối tượng phiếu đề nghị đặt hàng, chứa các dòng chi tiết và tổng chi phí. |
| `ChiTietPhieuDatHang.php` | Đối tượng một dòng sản phẩm của phiếu đặt hàng. |

#### `php/Repository/`

| File | Vai trò |
|---|---|
| `TaiKhoanRep.php` | Đọc tài khoản, chuẩn hóa vai trò và xác thực đăng nhập. |
| `NhanVienRep.php` | Truy vấn/ghi nhân viên và tài khoản liên kết. |
| `NhaCungCapRep.php` | CRUD nhà cung cấp và kiểm tra ràng buộc tham chiếu. |
| `PhieuDatHangRepository.php` | Đọc sản phẩm/nhà cung cấp và lưu phiếu đặt hàng/chi tiết. |
| `HoanTraRepository.php` | Đọc và cập nhật trạng thái yêu cầu hoàn trả. |

### `html/`

| File | Trạng thái/vai trò |
|---|---|
| `GD_DangNhap.php` | Trang đăng nhập thật; gọi `DangNhapController`. |
| `GD_TrangChu.php` | Dashboard thật; yêu cầu đăng nhập và lấy thông tin/thống kê từ phiên/database. |
| `GD_QuanLyBanHang.php` | Màn hình bán hàng thật; giỏ hàng phía trình duyệt, gọi API thanh toán. |
| `GD_QuanLyNhanVien.php` | Giao diện CRUD nhân viên/hồ sơ cá nhân thật. |
| `GD_QuanLyNhaCungCap.php` | Giao diện CRUD nhà cung cấp thật. |
| `GD_LapPhieuDatHang.php` | Giao diện lập phiếu đề nghị đặt hàng thật. |
| `GD_XuLyHoanTra.php` | Danh sách và form xử lý yêu cầu hoàn trả thật. |
| `GD_BaoCao.php` | Trang chỉ mục báo cáo nhân viên và ca làm việc. |
| `Form_BaoCaoNhanVien.php` | Báo cáo chi tiết một nhân viên. |
| `Form_BaoCaoCa.php` | Báo cáo chi tiết một ca làm việc. |
| `logout.php` | Endpoint POST đăng xuất có kiểm tra token. |
| `GD_TrangChu.html` | Dashboard tĩnh với tài khoản/thống kê viết sẵn; có liên kết logout GET, không thay thế trang PHP. |
| `GD_QuanLyNhanVien.html` | Mockup tĩnh có hàng nhân viên mẫu; các liên kết thao tác không phải luồng CRUD của trang PHP hiện tại. |
| `GD_QuanLyKhuyenMai.html` | Mockup tĩnh khuyến mãi có dữ liệu giả; chưa có màn hình PHP, một số liên kết CRUD trỏ nhầm sang route nhân viên. |
| `GD_LapPhieuDatHang.html` | Mockup tĩnh với dữ liệu sản phẩm mẫu; nút lập phiếu trỏ tới `form_lap_phieu.php`, không dùng luồng PHP hiện tại. |
| `GD_DuyetPhieuDatHang.html` | Mockup tĩnh; nút duyệt/từ chối gọi `xuly_duyet.php` bằng GET, endpoint đó chưa có trong danh mục project. |
| `giao-dien.css` | CSS giao diện tổng quát cũ. |
| `khung-giao-dien.css` | CSS dùng chung cho khung/sidebar các trang PHP. |

### `ThanhToan/`

| File | Vai trò |
|---|---|
| `helpers.php` | Đọc POST, đọc mã sản phẩm và định dạng mã hiển thị. |
| `xulythanhtoan.php` | API JSON cho tìm sản phẩm, thanh toán, xác nhận và hủy chuyển khoản. |
| `thanh_toan.php` | Trang thanh toán hóa đơn kiểu cũ, tách biệt với API bán hàng mới. |
| `hoadon.php` | Trang chi tiết hóa đơn chỉ đọc/in. |

## 6. Hàm và lớp

### 6.1. Kết nối MySQLi — `db.php`

- `eightmart_mysqli_connection()` — tạo và giữ một kết nối MySQLi duy nhất; thiết lập charset `utf8`; ném exception nếu kết nối/charset lỗi.
- `eightmart_mysqli_execute($sql, $params = array())` — prepare câu SQL, suy luận kiểu bind (`i`, `d`, `s`), gắn tham số, thực thi và trả `mysqli_stmt`.
- `eightmart_mysqli_fetch_all_assoc($statement)` — đọc metadata và bind kết quả để trả danh sách các dòng associative; đóng statement.
- `eightmart_mysqli_fetch_one_assoc($statement)` — trả dòng đầu tiên hoặc `null`.
- `eightmart_mysqli_fetch_value($statement)` — trả giá trị cột đầu tiên của dòng đầu hoặc `null`.
- `eightmart_mysqli_affected_rows($statement)` — lấy số dòng ảnh hưởng rồi đóng statement.
- `eightmart_mysqli_begin_transaction()` — gửi `START TRANSACTION`.
- `eightmart_mysqli_commit()` — gửi `COMMIT`.
- `eightmart_mysqli_rollback()` — gửi `ROLLBACK`.

Các repository dùng helper này để tránh mở nhiều kết nối và dùng prepared statement cho nhiều truy vấn. Một số file thanh toán cũ/API dùng trực tiếp `mysqli_query`; xem ghi chú tại phần chức năng thanh toán.

### 6.2. Bảo mật và phiên — `BaoMat`, `XacThuc`

#### `BaoMat` (`php/BaoMat.php`)

- `bangNhau($a, $b)` — so sánh hai chuỗi cùng độ dài bằng phép XOR tích lũy, tránh dừng sớm theo vị trí ký tự khác nhau.
- `taoNgauNhien($soByte)` — sinh byte ngẫu nhiên, ưu tiên OpenSSL, sau đó mcrypt; mã hiện có fallback `mt_rand` nếu không có hai extension trên.
- `pbkdf2($matKhau, $saltHex, $soVong)` — hàm private tự tính PBKDF2-HMAC-SHA256.
- `bamMatKhau($matKhau)` — sinh salt 16 byte và chuỗi lưu dạng `pbkdf2_sha256$...`, mặc định 30.000 vòng.
- `kiemTraMatKhau($matKhau, $daLuu)` — xác minh hash MD5 32 ký tự, PBKDF2 có số vòng hợp lệ hoặc bcrypt nếu môi trường hỗ trợ; định dạng không nhận diện trả `false`.

#### `XacThuc` (`php/XacThuc.php`)

- `batDau()` — khởi động session bằng cookie nếu chưa có session.
- `layNguoiDung()` — kiểm tra session, thời gian hoạt động và đọc lại trạng thái/quyền từ DB; hết hạn hoặc tài khoản không hợp lệ trả `null`.
- `yeuCauDangNhap()` — yêu cầu session hợp lệ; nếu không thì redirect về đăng nhập và dừng request.
- `dangNhap($nguoiDung)` — tái tạo session ID và lưu dữ liệu người dùng/thời gian hoạt động.
- `layTokenDangXuat()` — cấp hoặc lấy token dùng cho thao tác logout.
- `kiemTraTokenDangXuat($token)` — xác minh token logout.
- `dangXuat()` — xóa dữ liệu session, cookie và hủy session.

### 6.3. Đăng nhập — `DangNhapController`, `TaiKhoanRep`

#### `DangNhapController` (`php/Control/dangNhapController.php`)

- `kiemTraCuPhapDauVao($tenDangNhap, $matKhau)` — kiểm tra các trường bắt buộc, độ dài và tập ký tự tên đăng nhập; trả lỗi theo trường.
- `xuLy()` — xử lý GET/POST, kiểm tra CSRF, xác thực, tăng bộ đếm đăng nhập lỗi, tạo session khi đúng và trả view-model/redirect. Mã có ghi thời điểm khóa sau nhiều lần sai nhưng phần kiểm tra thực thi khóa đang bị comment, nên không nên xem đó là giới hạn thử mật khẩu đang hoạt động.

#### `TaiKhoanRep` (`php/Repository/TaiKhoanRep.php`)

- `__construct()` — lấy kết nối MySQLi dùng chung.
- `chuanHoaVaiTro($vaiTro)` — ánh xạ `QuanLy`/`ChuDoanhNghiep` sang vai trò chủ; vai trò khác thành nhân viên.
- `layNguoiDungTheoTaiKhoan($taiKhoanID)` — đọc tài khoản và nhân viên, chỉ trả dữ liệu khi cả hai đang hoạt động.
- `xacThuc($tenDangNhap, $matKhau)` — tra theo CCCD nếu tên đăng nhập là 12 số, ngược lại tra theo username; kiểm tra trạng thái và hash; trả dữ liệu người dùng hoặc `null`.

### 6.4. Nhân viên — `NhanVien`, `NhanVienController`, `NhanVienRep`

#### `NhanVien` (`php/Entity/NhanVien.php`)

- `__construct($ten, $cccd, $gioiTinh, $sdt, $ngaySinh)` — khởi tạo qua setter.
- `tuDong($row)` — ánh xạ hàng DB thành entity, gồm vai trò/tài khoản nếu có.
- `getMaNhanVien()` / `setMaNhanVien($v)` — đọc/ghi ID.
- `getTen()` / `setTen($v)` — đọc/ghi tên; chuẩn hóa khoảng trắng.
- `getCccd()` / `setCccd($v)` — đọc/ghi CCCD; bỏ khoảng trắng.
- `getGioiTinh()` / `setGioiTinh($v)` — đọc/ghi giới tính.
- `getSdt()` / `setSdt($v)` — đọc/ghi số điện thoại; bỏ khoảng trắng.
- `getNgaySinh()` / `setNgaySinh($v)` — đọc/ghi ngày sinh; nhận ngày ISO và chuẩn hóa ngày `dd/mm/yyyy`.
- `getMatKhauNgaySinh()` — xác thực ngày sinh rồi trả mật khẩu mặc định theo `ddmmyyyy`.
- `getTenDangNhap()`, `getVaiTro()`, `getTrangThai()` — lấy thông tin tài khoản/trạng thái đã ánh xạ.
- `laChuDoanhNghiep()` — kiểm tra vai trò chủ doanh nghiệp.
- `getMaHienThi()` — định dạng mã nhân viên `NV...`.
- `getGioiTinhHienThi()` — chuẩn hóa cách hiển thị giới tính nữ.
- `getChucVuHienThi()` — trả nhãn chức vụ để hiển thị.
- `kiemTra()` — kiểm tra các trường bắt buộc, CCCD 12 số, điện thoại 10 số, ngày hợp lệ/không ở tương lai; trả mảng lỗi.

#### `NhanVienController` (`php/Control/nhanVienController.php`)

- `xuLy()` — yêu cầu đăng nhập; quyết định quyền chủ/nhân viên; kiểm tra CSRF và route GET/POST; trả danh sách/form/thông báo.
- `xuLyThem()` — kiểm tra dữ liệu và CCCD trùng; tạo mật khẩu từ ngày sinh; ghi nhân viên và tài khoản trong một transaction.
- `xuLySua()` — kiểm tra quyền/giá trị trùng; cập nhật nhân viên và đồng bộ username nếu username được tạo từ CCCD.
- `xuLyXoa()` — chặn xóa chủ doanh nghiệp hoặc nhân viên đang được dữ liệu nghiệp vụ tham chiếu.
- `post($k)` — lấy một giá trị POST hoặc chuỗi rỗng.
- `giaTri(NhanVien $nv)` — chuyển entity thành dữ liệu form.
- `bieuMau($mode, $ma, $values, $errors = array(), $thongBaoLoi = '')` — đóng gói dữ liệu hiển thị form.
- `chuyenHuong($loai, $noiDung)` — lưu thông báo flash rồi redirect.

#### `NhanVienRep` (`php/Repository/NhanVienRep.php`)

- `__construct()` — giữ kết nối DB dùng chung.
- `batDauGiaoDich()`, `xacNhan()`, `hoanTac()` — quản lý transaction.
- `layTatCa()` / `layTheoMa($ma)` — đọc nhân viên và tài khoản liên quan, ánh xạ thành entity.
- `tonTaiCccd($cccd, $boQuaMa = 0)` / `tonTaiTenDangNhap($ten, $boQuaMa = 0)` — kiểm tra trùng.
- `them(NhanVien $nv)` — thêm nhân viên và trả ID mới.
- `taoTaiKhoan($ma, $ten, $pass)` — băm mật khẩu rồi thêm tài khoản.
- `capNhat(NhanVien $nv, $choDoiCccd = true)` — cập nhật hồ sơ và username CCCD nếu phù hợp.
- `dangDuocThamChieu($ma)` — kiểm tra các khóa ngoại/dữ liệu đang tham chiếu nhân viên.
- `xoa($ma)` — xóa tài khoản và nhân viên trong transaction nếu không bị tham chiếu.

### 6.5. Nhà cung cấp — `NhaCungCap`, `NhaCungCapController`, `NhaCungCapRep`

#### `NhaCungCap` (`php/Entity/NhaCungCap.php`)

- `__construct($ten, $email, $diaChi, $loaiSanPham, $trangThai)` — khởi tạo entity qua setter.
- `tuDong($row)` — ánh xạ hàng DB.
- `getMa()` / `setMa($ma)` — ID nhà cung cấp.
- `getTen()` / `setTen($ten)` — tên.
- `getEmail()` / `setEmail($email)` — email.
- `getDiaChi()` / `setDiaChi($diaChi)` — địa chỉ.
- `getLoaiSanPham()` / `setLoaiSanPham($loai)` — nhóm sản phẩm cung cấp.
- `getTrangThai()` / `setTrangThai($trangThai)` — trạng thái.
- `getMaHienThi()` — định dạng mã `NCC...`.
- `kiemTra()` — kiểm tra tên, độ dài, email nếu có và trạng thái; trả lỗi theo trường.

#### `NhaCungCapController` (`php/Control/nhaCungCapController.php`)

- `xuLy()` — nhận action GET/POST, quản lý flash, gọi repository và trả view-model.
- `xuLyThem()` — tạo entity, kiểm tra tên trùng, thêm trong transaction.
- `xuLySua()` — tải entity cũ, kiểm tra dữ liệu/trùng và cập nhật trong transaction.
- `xuLyXoa()` — kiểm tra tồn tại và dữ liệu liên quan rồi xóa.
- `entityTuPost()` — tạo entity từ form POST.
- `giaTriTrong()` — giá trị mặc định cho form trống.
- `giaTriTuEntity($ncc)` — chuyển entity thành dữ liệu form.
- `bieuMau($mode, $ma, $values, $errors, $loiChung)` — đóng gói view-model form.
- `post($key)` — đọc và trim trường POST.
- `chuyenHuong($type, $message)` — ghi flash và redirect.

#### `NhaCungCapRep` (`php/Repository/NhaCungCapRep.php`)

- `__construct()` — lấy kết nối MySQLi.
- `batDauGiaoDich()`, `xacNhan()`, `hoanTac()` — điều khiển transaction.
- `layTatCa()` / `layTheoMa($ma)` — đọc danh sách/chi tiết và tạo entity.
- `tonTaiTen($ten, $boQuaMa = 0)` — kiểm tra tên trùng.
- `them(NhaCungCap $ncc)` — insert và trả ID.
- `capNhat(NhaCungCap $ncc)` — cập nhật nhà cung cấp.
- `dangDuocThamChieu($ma)` — kiểm tra liên kết sản phẩm và chi tiết phiếu đặt.
- `xoa($ma)` — xóa và trả kết quả.

### 6.6. Đặt hàng — entity, controller và repository

#### `ChiTietPhieuDatHang` (`php/Entity/ChiTietPhieuDatHang.php`)

- `__construct($ma_san_pham, $so_luong, $don_gia, $ma_phieu = null)` — lưu ID sản phẩm, số lượng, giá và ID phiếu tùy chọn.
- `getMaSanPham()`, `getSoLuong()`, `getDonGia()` — lấy dữ liệu dòng.
- `getThanhTien()` — tính số lượng nhân đơn giá.
- `setMaPhieu($ma_phieu)` — gắn dòng với phiếu.

#### `PhieuDeNghiDatHang` (`php/Entity/PhieuDeNghiDatHang.php`)

- `__construct($ma_nhan_vien, $ma_nha_cung_cap, $tong_chi_phi = 0, $trang_thai = 'ChoDuyet', $ngay_lap = null)` — khởi tạo phiếu; ngày lập mặc định là thời điểm hiện tại.
- `getMaNhanVien()`, `getMaNhaCungCap()`, `getNgayLap()`, `getTrangThai()`, `getTongChiPhi()` — lấy thông tin đầu phiếu.
- `themChiTiet(ChiTietPhieuDatHang $chi_tiet)` — thêm dòng và cộng tổng chi phí.
- `getDanhSachChiTiet()` — lấy danh sách dòng.

#### `LapPhieuDatHangControl` (`php/Control/lapPhieuDatHangControl.php`)

- `__construct($db_connection)` — tạo repository với kết nối được truyền vào.
- `layDanhSachNCC()` — lấy nhà cung cấp.
- `layDanhSachNCCTheoSanPham($maSanPham)` — lấy NCC có thể cung cấp sản phẩm.
- `layDanhSachSanPhamCanNhap()` — lấy sản phẩm cần bổ sung tồn.
- `xuLyLapPhieu($ma_nv, $ds_sp_duoc_chon, $ds_ncc, $ds_so_luong, $ds_don_gia)` — nhóm sản phẩm theo NCC, tạo entity và lưu phiếu; số lượng hợp lệ 1–100.
- `layThongTinPhieuVuaTao($ma_phieu)` — lấy thông tin phiếu vừa tạo.
- `layChiTietPhieuVuaTao($ma_phieu)` — lấy các dòng của phiếu.

#### `PhieuDatHangRepository` (`php/Repository/PhieuDatHangRepository.php`)

- `__construct($db_connection)` — giữ kết nối.
- `getDanhSachNhaCungCap()` — liệt kê NCC đang hoạt động.
- `getDanhSachNhaCungCapTheoSanPham($maSanPham)` — lọc NCC theo sản phẩm.
- `getSanPhamCanNhap()` — sản phẩm hoạt động dưới/ngang tồn tối thiểu và chưa có phiếu đang chờ.
- `savePhieuDatHang(PhieuDeNghiDatHang $phieu)` — xác minh NCC/sản phẩm, thêm đầu phiếu và chi tiết trong transaction.
- `getPhieuVuaTao($ma_phieu)` — đọc thông tin đầu phiếu.
- `getChiTietPhieuVuaTao($ma_phieu)` — đọc các dòng chi tiết.

### 6.7. Hoàn trả — controller và repository

#### `HoanTraController` (`php/Control/hoanTraController.php`)

- `__construct()` — tạo repository.
- `layDanhSach()` — lấy yêu cầu, ưu tiên trạng thái chờ duyệt.
- `chapNhan($maYeuCau, $maNguoiXuLy = null)` — chỉ chuyển trạng thái nếu yêu cầu tồn tại và còn chờ xử lý.
- `tuChoi($maYeuCau, $lyDo, $maNguoiXuLy = null)` — kiểm tra lý do (không rỗng, tối đa 500 ký tự UTF-8), sau đó cập nhật trạng thái/lý do.
- `kiemTraDangChoDuyet($maYeuCau)` — hàm private kiểm tra ID và trạng thái hiện thời.
- `doDai($s)` — đếm ký tự UTF-8 bằng mbstring hoặc fallback regex.
- `loiHeThong($e)` — ghi log lỗi và trả lỗi tổng quát.
- `ketQua($ok, $message)` — đóng gói kết quả `{ok, message}`.

#### `HoanTraRepository` (`php/Repository/HoanTraRepository.php`)

- `db()` — khởi tạo kết nối dùng chung khi cần.
- `layDanhSach($trangThaiCho)` — lấy yêu cầu, hóa đơn và tổng số lượng trả; sắp xếp theo trạng thái/ngày.
- `layTrangThai($maYeuCau)` — lấy trạng thái hoặc `null`.
- `capNhatTrangThai($maYeuCau, $trangThaiMoi, $lyDoTuChoi, $maNguoiXuLy, $trangThaiHienTai)` — cập nhật có điều kiện theo trạng thái cũ; trả số dòng bị tác động.

`php/Control/xuLyHoanTra.php` không khai báo hàm/lớp; đây là endpoint POST phân nhánh action chấp nhận/từ chối, lưu flash và redirect về giao diện.

### 6.8. Báo cáo — `BaoCao`

`BaoCao` nằm trong `php/Control/baoCao.php`:

- `__construct()` — lấy kết nối DB.
- `rows($sql)` — hàm private chạy truy vấn và trả danh sách associative; ném exception khi lỗi.
- `id($value)` — hàm private kiểm tra ID số nguyên dương trong miền `INT`.
- `getDanhSachNhanVien()` — lấy danh sách nhân viên kèm vai trò.
- `getDanhSachCa()` — lấy danh sách ca mới nhất trước.
- `thongKe($field, $id)` — hàm private đếm và tính doanh thu của hóa đơn đủ điều kiện.
- `getBaoCaoNhanVien($maNV)` — trả `{thong_tin, thong_ke}` hoặc `null`.
- `getBaoCaoCa($maCa)` — trả `{thong_tin, thong_ke}` hoặc `null`.

### 6.9. Bán hàng — helper PHP, API và JavaScript

#### `ThanhToan/helpers.php`

- `lay_post($ten, $mac_dinh = '')` — đọc giá trị POST, hỗ trợ xử lý magic quotes trên PHP cũ.
- `ma_sp_tu_chuoi($chuoi)` — lấy mã số sản phẩm từ chuỗi nhập, ví dụ mã hiển thị.
- `ma_sp_hien_thi($so)` — tạo mã sản phẩm dạng `SP...`.

#### `ThanhToan/xulythanhtoan.php`

- `tra_ve($du_lieu)` — JSON-encode dữ liệu, xuất response và dừng request.
- `bao_loi($conn, $thong_bao)` — rollback, khôi phục autocommit và trả lỗi JSON.
- `chay($conn, $sql)` — chạy truy vấn; nếu lỗi thì đi qua `bao_loi`.
- `lay_ca_dang_mo($conn, $ma_nhan_vien)` — lấy ca mở của nhân viên hoặc tạo ca mới.
- `danh_dau_huy($conn, $ma_thanh_toan, $ma_hoa_don, $ly_do)` — đánh dấu thanh toán thất bại và hóa đơn bị hủy.
- `tim_san_pham($conn)` — API tra sản phẩm hoạt động theo mã.
- `thanh_toan($conn, $ma_nhan_vien)` — kiểm tra giỏ hàng/phương thức/tồn kho; tạo hóa đơn, chi tiết và thanh toán; trừ kho ngay với tiền mặt.
- `xac_nhan_chuyen_khoan($conn, $ma_nhan_vien)` — khóa bản ghi thanh toán chờ, kiểm tra thời hạn/tồn kho, ghi nhận người xác nhận và hoàn tất hóa đơn.
- `huy_chuyen_khoan($conn)` — hủy thanh toán đang chờ và đánh dấu hóa đơn tương ứng.

Các action API: GET `action=tim_sp`; POST `action=thanh_toan`, `action=xac_nhan`, `action=huy`. Response là JSON. Mã QR hiện dùng URL dịch vụ VietQR và thông tin tài khoản được cấu hình trực tiếp ở giao diện, không phải tích hợp cổng thanh toán tự động.

#### JavaScript trong `html/GD_QuanLyBanHang.php`

- `tien(so)` — định dạng tiền Việt; `tongTien()` — cộng thành tiền giỏ hàng.
- `goiAPI(url, phuongThuc = 'GET', duLieu = null)` — gọi endpoint, đọc JSON và báo lỗi khi response không phải JSON.
- `veBang()` — vẽ lại bảng giỏ hàng và tổng tiền.
- `themSanPhamTuDanhSach(maSP, tonKho)` — chọn sản phẩm từ danh sách, bỏ qua sản phẩm hết hàng.
- `themSanPham()` — tra mã qua API, cộng dồn sản phẩm trùng và kiểm tra số lượng tồn.
- `doiSoLuong(i, giaTri)` — giới hạn số lượng theo tồn kho và cập nhật giỏ.
- `xoaDong(i)` — xóa một dòng giỏ hàng.
- `hienBuoc(id)` — chuyển bước trong modal thanh toán và cập nhật tổng tiền.
- `moThanhToan()` / `dongThanhToan()` — mở khi giỏ có hàng / đóng modal.
- `chonPhuongThuc(pt)` — hiển thị luồng tiền mặt hoặc gửi yêu cầu chuyển khoản.
- `docSo(chuoi)` — chuyển chuỗi tiền nhập thành số nguyên.
- `tinhTienThua()` — tính tiền thừa hoặc báo khách chưa đưa đủ.
- `guiThanhToan(phuongThuc)` — gửi giỏ hàng; xử lý kết quả tiền mặt hoặc khởi tạo QR và đếm ngược.
- `demNguoc(giaTri)` — hiển thị thời gian còn lại và kích hoạt hủy khi hết hạn.
- `huyTheoMa()` — gửi yêu cầu hủy do hết hạn.
- `huyDonChuyenKhoan()` — hỏi xác nhận rồi hủy chuyển khoản.
- `xacNhanChuyenKhoan()` — gửi yêu cầu xác nhận đã nhận tiền.
- `hienKetQua(noiDung, maHoaDon)` — hiển thị kết quả và tạo liên kết in hóa đơn.
- `hoaDonMoi()` — xóa trạng thái giỏ/thanh toán để bắt đầu hóa đơn mới.
- Sự kiện Enter trên ô mã sản phẩm gọi `themSanPham()`.

### 6.10. Hàm hỗ trợ giao diện

- `hDangNhap($value)` trong `GD_DangNhap.php` — escape dữ liệu khi in HTML.
- `hTrangChu($chuoi)` trong `GD_TrangChu.php` — escape output dashboard.
- `hSanPham($giaTri)` trong `GD_QuanLyBanHang.php` — escape output sản phẩm.
- `h($v)` trong `GD_QuanLyNhanVien.php` và `GD_QuanLyNhaCungCap.php` — escape HTML.
- `h($value)`, `money($value)`, `fmtDate($value)` trong `GD_BaoCao.php`, `Form_BaoCaoNhanVien.php`, `Form_BaoCaoCa.php` — escape, định dạng tiền và ngày/giờ; ngày `null` hiển thị “Chưa kết thúc”.
- `trangThaiNcc($status)` trong `GD_QuanLyNhaCungCap.php` — đổi trạng thái NCC sang nhãn hiển thị.
- `e($s)`, `hienMaYeuCau($id)`, `hienMaHoaDon($id)`, `hienTrangThai($tt)` trong `GD_XuLyHoanTra.php` — escape, định dạng mã yêu cầu/hóa đơn và nhãn trạng thái.
- `tien($so)` trong `ThanhToan/hoadon.php` — định dạng số tiền trên hóa đơn.

JavaScript khác: trang đăng nhập có nút hiện/ẩn mật khẩu; form nhân viên kiểm tra từng trường và modal xác nhận xóa; trang nhà cung cấp có modal xác nhận xóa; trang hoàn trả có form/modal từ chối và kiểm tra lý do. Các trang `.html` mẫu có confirm/document.write trực tiếp, không dùng chung nghiệp vụ phía server.

## 7. Cơ sở dữ liệu

Database mặc định là `eightmart`. [schema.sql](schema.sql) tạo các bảng sau:

| Bảng | Mục đích và liên kết chính |
|---|---|
| `NhanVien` | Hồ sơ nhân viên; được tham chiếu bởi tài khoản, ca, hóa đơn, phiếu và người xử lý. |
| `TaiKhoan` | Tên đăng nhập, hash mật khẩu, vai trò/trạng thái; một tài khoản gắn với một nhân viên. |
| `KhachHang` | Khách hàng; schema hiện có ID tối giản, mỗi giao dịch bán có thể tạo bản ghi mới. |
| `NhaCungCap` | Thông tin nhà cung cấp. |
| `SanPham` | Tên, giá bán, trạng thái và số lượng/tồn tối thiểu/mục tiêu. |
| `ChuongTrinhKhuyenMai` | Thời gian, hình thức, mức giảm và trạng thái khuyến mãi. |
| `CaLamViec` | Thời gian/trạng thái ca, nhân viên mở và nhân viên đóng. |
| `HoaDon` | Đầu hóa đơn; liên kết khách hàng, nhân viên và ca. |
| `HoaDon_ChuongTrinhKhuyenMai` | Bảng nối hóa đơn và chương trình khuyến mãi. |
| `ChiTietHoaDon` | Sản phẩm, số lượng, đơn giá và thành tiền; khóa chính ghép hóa đơn/sản phẩm. |
| `ThanhToan` | Phương thức, số tiền, trạng thái, thời gian, nhân viên xác nhận và hóa đơn/khách hàng. |
| `PhieuYeuCauTraHang` | Yêu cầu trả hàng, lý do, trạng thái, nhân viên yêu cầu/xử lý và thời điểm. |
| `ChiTietPhieuYeuCauTraHang` | Sản phẩm/số lượng trả; ràng buộc tới yêu cầu và dòng hóa đơn. |
| `NhaCungCap_SanPham` | Bảng nối sản phẩm và nhà cung cấp. |
| `PhieuDeNghiDatHang` | Đầu phiếu đề nghị, nhân viên lập/duyệt, tổng chi phí, trạng thái và thời điểm. |
| `ChiTietPhieuDeNghiDatHang` | Sản phẩm, số lượng, giá và NCC trong một phiếu. |
| `PhieuNhapKho` | Phiếu nhập, nhân viên và phiếu đề nghị liên quan. |
| `ChiTietPhieuNhapKho` | Số lượng thực nhận cho các dòng phiếu nhập/đề nghị. |

Các thời điểm tạo/lập/thanh toán quan trọng có `DEFAULT CURRENT_TIMESTAMP` trong schema; các thời điểm kết thúc/xử lý có thể `NULL`. Ứng dụng cũng gửi thời gian cụ thể (`NOW()` hoặc timestamp từ form) ở một số luồng.

## 8. Các điểm chưa hoàn chỉnh/cần xác minh

1. **Mật khẩu MD5:** đăng nhập hiện hỗ trợ kiểm tra MD5 theo yêu cầu, nhưng MD5 là thuật toán yếu và không phù hợp để lưu mật khẩu thật. Mã không tự nâng cấp các hash MD5 sau khi đăng nhập.
2. **Hai file SQL khác nhau:** `schema.sql` là cấu trúc mới; `eightmart.sql` là dump cũ. Cần chọn đúng file và database, không import chồng.
3. **Trang mẫu không phải chức năng thật:** `.html` không truy cập DB. Đặc biệt, trang duyệt đặt hàng và trang khuyến mãi chưa có luồng PHP hoàn chỉnh.
4. **Phân quyền không đồng nhất:** các trang nhân viên có kiểm soát vai trò; một số luồng như quản lý nhà cung cấp/báo cáo chỉ cho thấy yêu cầu đăng nhập chứ không thấy kiểm tra vai trò chủ ở tầng controller. Kiểm tra lại trước khi triển khai nhiều vai trò.
5. **CSRF:** luồng đăng nhập, đăng xuất, nhân viên có token; một số form/API bán hàng, nhà cung cấp và hoàn trả không thể hiện token CSRF trong các trang/endpoint đã rà soát.
6. **Xác nhận chuyển khoản:** thao tác là nhân viên xác nhận thủ công; QR không tự đối soát giao dịch ngân hàng.
7. **Dữ liệu tiếng Việt:** `NVARCHAR` không sửa dữ liệu đã hỏng; dùng UTF-8 xuyên suốt khi import/nhập dữ liệu.
8. **Mật khẩu mặc định nhân viên:** tạo từ ngày sinh (`ddmmyyyy`) và được băm PBKDF2 trước khi lưu. Đây là mật khẩu dễ đoán; nên yêu cầu đổi mật khẩu khi triển khai thật.
9. **Giới hạn phiên bản:** PHP 5.2.6/MySQL 5.0.51b đã lỗi thời; `TIMESTAMP` trên MySQL cũ có giới hạn năm 2038. Nâng cấp cần kiểm thử tương thích trước.
10. **Không có bộ test/build tự động được phát hiện:** xác minh thủ công các luồng đăng nhập, CRUD, bán hàng, thanh toán/hủy, hoàn trả và báo cáo sau khi import schema.

## 9. Gợi ý kiểm tra khi có lỗi

- Lỗi kết nối: kiểm tra MySQL đang chạy, database `eightmart` đã tồn tại và thông tin trong `db.php`.
- Lỗi “table/column not found”: rất có thể đã import `eightmart.sql` cũ thay vì `schema.sql`.
- Không đăng nhập được: xác minh tài khoản đang hoạt động, trạng thái nhân viên đang hoạt động, username tra đúng và mật khẩu khớp hash MD5/PBKDF2/bcrypt được `BaoMat` hỗ trợ.
- Tiếng Việt sai dấu: kiểm tra encoding file SQL UTF-8, `SET NAMES utf8`, charset connection và dữ liệu gốc trong DB.
- Hóa đơn không hoàn tất: kiểm tra ca làm việc, tồn kho, trạng thái payment/invoice và lỗi MySQL; API trả lỗi JSON.
- Màn hình không có chức năng khi bấm: xác nhận liên kết đang trỏ tới file `.php` hoạt động, không phải `.html` mockup hoặc endpoint chưa được tạo.
