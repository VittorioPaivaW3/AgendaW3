<?php
// bootstrap.php — setup comum (DB + migrações + utilitários)
// Requisitos: PHP 8+, pdo_sqlite habilitado.

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// === Conexão SQLite ===
$dbFile = __DIR__ . '/database.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// === Tabelas base ===
$pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT '#3b82f6'
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    date_start TEXT NOT NULL,
    date_end   TEXT NOT NULL,
    participants TEXT,
    is_online INTEGER NOT NULL DEFAULT 0,
    meeting_link TEXT,
    notes TEXT,
    created_at TEXT NOT NULL,
    requester TEXT,
    participants_count INTEGER NOT NULL DEFAULT 1,
    needs_coffee INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);");

// === Migração leve: garante colunas novas sem perder dados ===
function ensureColumn(PDO $pdo, string $table, string $column, string $ddl) {
    $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    $names = array_map(fn($c)=>$c['name'], $cols);
    if (!in_array($column, $names, true)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
    }
}

// rooms: extras, capacidade e bloqueio
ensureColumn($pdo, 'rooms', 'capacity',    "capacity INTEGER NOT NULL DEFAULT 8");
ensureColumn($pdo, 'rooms', 'has_wifi',    "has_wifi INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'rooms', 'has_tv',      "has_tv INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'rooms', 'has_board',   "has_board INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'rooms', 'has_ac',      "has_ac INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'rooms', 'is_blocked',  "is_blocked INTEGER NOT NULL DEFAULT 0");

// bookings: garantias (para bases antigas)
ensureColumn($pdo, 'bookings', 'requester',            "requester TEXT");
ensureColumn($pdo, 'bookings', 'participants_count',   "participants_count INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'bookings', 'needs_coffee',         "needs_coffee INTEGER NOT NULL DEFAULT 0");

// === Seed inicial de salas ===
$roomsCount = (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
if ($roomsCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO rooms (name, color, capacity, has_wifi, has_tv, has_board, has_ac, is_blocked) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute(['Sala A', '#3b82f6', 8, 1, 1, 1, 1, 0]);
    $stmt->execute(['Sala B', '#10b981', 12, 1, 1, 1, 1, 0]);
    $stmt->execute(['Sala C', '#f59e0b', 6, 1, 0, 1, 1, 0]);
}

// === Utilitários globais ===
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function parseDateTime($date, $time) { return DateTime::createFromFormat('Y-m-d H:i', "$date $time", new DateTimeZone('America/Sao_Paulo')); }
function toSql(DateTime $dt) { return $dt->format('Y-m-d H:i:s'); }
function hh($h) { return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00'; }
function withinBusinessHours(DateTime $start, DateTime $end): bool {
    // 08:00 a 18:00, permite finalizar exatamente às 18:00
    $open = (clone $start); $open->setTime(8,0,0);
    $closeStart = (clone $start); $closeStart->setTime(18,0,0);
    $openEnd = (clone $end); $openEnd->setTime(8,0,0);
    $closeEnd = (clone $end); $closeEnd->setTime(18,0,0);
    return ($start >= $open && $start <= $closeStart) && ($end >= $openEnd && $end <= $closeEnd) && ($end > $start);
}
function bookingsForCell(array $list, DateTime $cellStart, DateTime $cellEnd): array {
    $out = [];
    foreach ($list as $b) {
        $bStart = new DateTime($b['date_start']);
        $bEnd   = new DateTime($b['date_end']);
        if ($cellStart < $bEnd && $cellEnd > $bStart) { $out[] = $b; }
    }
    return $out;
}
?>
