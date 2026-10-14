<?php
require 'config.php';
require_login();
$uid = (int)$_SESSION['uid'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$isAdmin = !empty($_SESSION['is_admin']);

$stmt = $con->prepare('SELECT 1 FROM picks WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$already = (bool)$row;

$stmt = $con->prepare('SELECT COUNT(*) AS c FROM names WHERE LOWER(name)<>LOWER(?)');
$stmt->bind_param('s', $username);
$stmt->execute();
$count = (int)$stmt->get_result()->fetch_assoc()['c'];

$rows = $con->query('SELECT u.id, u.username, p.id AS nid FROM users u LEFT JOIN picks p ON p.user_id=u.id ORDER BY u.username')
            ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard · Christmas Wheel</title>
<link rel="stylesheet" href="style.css"></head>
<body><div class="snow"></div>
<header>
  <div class="who">
    <span class="chip"><?= e($email) ?></span>
    <span class="chip">🎅 <?= e($username) ?></span>
  </div>
  <a class="logout" href="logout.php">Log out</a>
</header>

<main>
  <h1>🎄 Christmas Wheel 🎁</h1>
  <div class="wheel-wrap">
    <div class="pointer"></div>
    <canvas id="wheel" width="500" height="500"></canvas>
    <button id="spin" <?= ($already || $count < 1) ? 'disabled' : '' ?>>Spin</button>
  </div>
  <p id="status" class="status">
    <?php if ($already): ?>You already picked. Your nabunot is hidden from everyone else.
    <?php elseif ($count < 1): ?>No names left on the wheel.<?php endif; ?>
  </p>

  <section class="picks">
    <h2>Nabunot list</h2>
    <table id="picks">
      <thead><tr><th>User name</th><th>Nabunot</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $picked = !empty($r['nid']);
        $can = $picked && $isAdmin; ?>
        <tr data-uid="<?= (int)$r['id'] ?>" data-picked="<?= $picked ? 1 : 0 ?>">
          <td><?= e($r['username']) ?><?= (int)$r['id'] === $uid ? ' (you)' : '' ?></td>
          <td class="pick"><?= $picked ? '🎁 nabunot (hidden)' : '— not yet' ?></td>
          <td>
            <button class="reveal" <?= $can ? '' : 'disabled' ?>>Reveal</button>
            <button class="hide">Hide</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<!-- Popup overlay: confetti canvas sits BEHIND the box, so it rains around it -->
<div class="modal" id="popup" hidden>
  <canvas id="confetti"></canvas>
  <div class="modal-box">
    <h3 id="pTitle"></h3>
    <p id="pName" class="big"></p>
    <p id="pNote" class="note"></p>
    <button id="pClose">Close</button>
  </div>
</div>

<script>
/* ---------------- Wheel ---------------- */
const COUNT = <?= $count ?>, ME = <?= $uid ?>, IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>, HIDDEN = '🎁 nabunot (hidden)';
const canvas = document.getElementById('wheel'), ctx = canvas.getContext('2d');
const colors = ['#c0392b', '#1e7a46', '#f2c14e', '#7a1f1f', '#2f9e5f'];

// Wheel is drawn WITHOUT names; the name only shows in the popup after it stops
(function draw() {
  const n = Math.max(COUNT, 2), seg = 2 * Math.PI / n, c = 250;
  for (let i = 0; i < n; i++) {
    ctx.beginPath(); ctx.moveTo(c, c);
    ctx.arc(c, c, c - 4, -Math.PI / 2 + i * seg, -Math.PI / 2 + (i + 1) * seg);
    ctx.fillStyle = colors[i % colors.length]; ctx.fill();
    ctx.strokeStyle = '#fff8e7'; ctx.lineWidth = 3; ctx.stroke();
  }
})();

/* ---------------- Popup ---------------- */
const popupEl = document.getElementById('popup');
const pTitle  = document.getElementById('pTitle');
const pName   = document.getElementById('pName');
const pNote   = document.getElementById('pNote');

// party = true -> confetti rains AROUND the popup box (box stays on top)
function popup(title, name, note, party) {
  pTitle.textContent = title;
  pName.textContent = name;
  pNote.textContent = note || '';
  popupEl.hidden = false;
  if (party) { sizeConfetti(); launchConfetti(); }
}

function closePopup() {
  popupEl.hidden = true;
  stopConfetti();
}

document.getElementById('pClose').onclick = closePopup;
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && !popupEl.hidden) closePopup();
});

/* ---------------- Confetti (full screen, behind the popup box) ---------------- */
const confettiCanvas = document.getElementById('confetti');
const cctx = confettiCanvas.getContext('2d');
const CONF_COLORS = ['#c0392b', '#1e7a46', '#f2c14e', '#fff8e7', '#e67e22', '#2f9e5f'];
let particles = [], confettiRAF = null;

function sizeConfetti() {
  confettiCanvas.width = window.innerWidth;
  confettiCanvas.height = window.innerHeight;
}
sizeConfetti();
window.addEventListener('resize', sizeConfetti);

function launchConfetti(count = 200) {
  for (let i = 0; i < count; i++) {
    particles.push({
      x: Math.random() * confettiCanvas.width,
      y: -20 - Math.random() * confettiCanvas.height * 0.5,
      vx: (Math.random() - 0.5) * 3,
      vy: 2 + Math.random() * 4,
      size: 6 + Math.random() * 8,
      rot: Math.random() * Math.PI * 2,
      vr: (Math.random() - 0.5) * 0.28,
      color: CONF_COLORS[Math.floor(Math.random() * CONF_COLORS.length)],
      shape: Math.random() < 0.5 ? 'rect' : 'circle',
      life: 0
    });
  }
  if (!confettiRAF) confettiRAF = requestAnimationFrame(tickConfetti);
}

function tickConfetti() {
  cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
  particles = particles.filter(p => p.y < confettiCanvas.height + 60 && p.life < 600);

  for (const p of particles) {
    p.life++;
    p.x += p.vx;
    p.y += p.vy;
    p.vy += 0.05;          // gravity
    p.rot += p.vr;

    cctx.save();
    cctx.translate(p.x, p.y);
    cctx.rotate(p.rot);
    cctx.fillStyle = p.color;
    if (p.shape === 'rect') {
      cctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
    } else {
      cctx.beginPath();
      cctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
      cctx.fill();
    }
    cctx.restore();
  }

  if (particles.length) {
    confettiRAF = requestAnimationFrame(tickConfetti);
  } else {
    confettiRAF = null;
    cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
  }
}

function stopConfetti() {
  particles = [];
  if (confettiRAF) { cancelAnimationFrame(confettiRAF); confettiRAF = null; }
  cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
}

/* ---------------- Spin ---------------- */
const spinBtn = document.getElementById('spin'), statusEl = document.getElementById('status');
let rotation = 0;
spinBtn.addEventListener('click', async () => {
  spinBtn.disabled = true;
  statusEl.textContent = 'Spinning… 🎡';
  let data;
  try { data = await (await fetch('spin.php', { method: 'POST' })).json(); }
  catch (err) { data = { error: 'Something went wrong. Try again.' }; }
  if (data.error) { statusEl.textContent = data.error; return; }

  const seg = 360 / Math.max(data.count, 2), center = (data.index + 0.5) * seg;
  rotation += 360 * 6 + (360 - (rotation % 360)) - center;
  canvas.style.transform = `rotate(${rotation}deg)`;

  canvas.addEventListener('transitionend', () => {
    statusEl.textContent = 'Your pick is saved and hidden from everyone else.';
    popup('🎉 You picked…', data.name);           // add a 4th arg of true here for confetti on your own spin too
    const tr = document.querySelector(`#picks tr[data-uid="${ME}"]`);
    tr.dataset.picked = 1;
    tr.querySelector('.pick').textContent = HIDDEN;
    tr.querySelector('.reveal').disabled = !IS_ADMIN;
  }, { once: true });
});

/* ---------------- Reveal / Hide buttons ---------------- */
document.getElementById('picks').addEventListener('click', async ev => {
  const btn = ev.target.closest('button'); if (!btn) return;
  const tr = btn.closest('tr'), cell = tr.querySelector('.pick');

  if (btn.classList.contains('hide')) {
    if (tr.dataset.picked === '1') cell.textContent = HIDDEN;
    return;
  }

  const res = await fetch('reveal.php?user_id=' + tr.dataset.uid);
  const d = await res.json();
  if (d.error) { popup('Oops', d.error); return; }

  cell.textContent = d.picked;
  // 🎉 confetti rains around the box + name shown inside it
  popup(d.username + ' picked 🎁', d.picked, '', true);
});
</script>
</body></html>