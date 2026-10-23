<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_name('printflow_sid');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}
function base_url(): string {
    $override = getenv('PRINT_BASE_URL');
    if ($override !== false && $override !== '') return rtrim($override, '/');
    $path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($path, '/public/');
    if ($pos !== false) return substr($path, 0, $pos + 7);
    if (str_ends_with($path, '/public')) return $path;
    return '';
}
function url(string $path = ''): string { return base_url() . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: '.url($path), true, 303); exit; }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(mixed $value): string { return '₱'.number_format((float)$value, 2); }
function nice_date(?string $date): string { return $date ? date('M j, Y · g:i A', strtotime($date)) : '—'; }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function require_csrf(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(419); exit('Your session expired. Go back, refresh the page, and try again.');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'][] = [$type, $message]; }
function consume_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    if ($role !== null && $u['role'] !== $role) { http_response_code(403); exit('Access denied.'); }
    return $u;
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function forbidden(): never { http_response_code(404); exit('The requested order could not be found.'); }
function get_order(int $id, array $user, bool $forUpdate = false): array {
    $sql = 'SELECT o.*, u.full_name AS customer_name, u.email AS customer_email, f.original_name, f.stored_name, f.file_size,
            p.amount AS paid_amount, p.paid_at, p.recorded_by AS payment_admin
            FROM print_orders o JOIN users u ON u.id=o.customer_id
            JOIN order_files f ON f.order_id=o.id
            LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=?';
    if ($user['role'] !== 'admin') $sql .= ' AND o.customer_id='.(int)$user['id'];
    if ($forUpdate) $sql .= ' FOR UPDATE';
    $s = db()->prepare($sql); $s->execute([$id]); $row = $s->fetch();
    if (!$row) forbidden();
    return $row;
}
function set_status(PDO $pdo, int $id, int $actor, string $from, string $to, string $note = ''): void {
    $s = $pdo->prepare('INSERT INTO order_status_history (order_id,actor_id,old_status,new_status,note) VALUES (?,?,?,?,?)');
    $s->execute([$id, $actor, $from, $to, $note ?: null]);
}
function status_label(string $status): string { return match($status) {
    'pending'=>'Pending', 'approved'=>'Approved', 'printing'=>'Printing', 'ready'=>'Ready for pickup',
    'completed'=>'Completed', 'rejected'=>'Rejected', 'canceled'=>'Canceled', default=>ucfirst($status)
}; }
function status_badge(string $status): string { return '<span class="status status-'.e($status).'"><span class="status-dot"></span>'.e(status_label($status)).'</span>'; }
function order_history(int $id): array {
    $s=db()->prepare('SELECT h.*,u.full_name AS actor_name FROM order_status_history h JOIN users u ON u.id=h.actor_id WHERE h.order_id=? ORDER BY h.id ASC');
    $s->execute([$id]); return $s->fetchAll();
}
function rates(): array {
    return db()->query('SELECT id,paper_size,print_color,price_per_page FROM print_rates WHERE is_active=1 ORDER BY FIELD(paper_size,"A4","Letter","Legal"), FIELD(print_color,"bw","color")')->fetchAll();
}
function rate_for(string $paper, string $color): ?array {
    $s=db()->prepare('SELECT * FROM print_rates WHERE paper_size=? AND print_color=? AND is_active=1 LIMIT 1');
    $s->execute([$paper,$color]); return $s->fetch() ?: null;
}
function quote_total(string $price, int $pages, int $copies): string {
    // Multiply exact integer cents rather than floating point money amounts.
    $cents = (int) round(((float)$price) * 100);
    $total = $cents * $pages * $copies;
    if ($total > 9999999999) throw new RuntimeException('This request exceeds the supported price limit.');
    return intdiv($total,100) . '.' . str_pad((string)($total % 100),2,'0',STR_PAD_LEFT);
}
function valid_positive_int(mixed $input, int $max): ?int {
    if (!is_scalar($input)) return null;
    $v=filter_var($input,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>$max]]);
    return $v === false ? null : (int)$v;
}
function order_no(): string { return 'PRT-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2))); }
