// Confetti
const confettiCanvas = document.createElement('canvas');
confettiCanvas.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:100;display:none;';
document.body.appendChild(confettiCanvas);
const cctx = confettiCanvas.getContext('2d');
let confettiParticles = [], confettiRAF = null;

function fireConfetti(duration = 2500) {
  confettiCanvas.width = window.innerWidth;
  confettiCanvas.height = window.innerHeight;
  confettiCanvas.style.display = 'block';
  const colors = ['#c0392b','#1e7a46','#f2c14e','#7a1f1f','#2f9e5f','#fff8e7'];
  const start = performance.now();
  confettiParticles = [];
  function spawn() {
    for (let i = 0; i < 6; i++) {
      confettiParticles.push({
        x: Math.random() * confettiCanvas.width,
        y: -10 - Math.random() * 40,
        vx: (Math.random() - 0.5) * 3,
        vy: 2 + Math.random() * 3,
        size: 6 + Math.random() * 6,
        color: colors[(Math.random() * colors.length) | 0],
        rot: Math.random() * Math.PI,
        vr: (Math.random() - 0.5) * 0.2,
      });
    }
  }
  function frame(now) {
    cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
    if (now - start < duration) spawn();
    confettiParticles.forEach(p => {
      p.x += p.vx; p.y += p.vy; p.vy += 0.05; p.rot += p.vr;
      cctx.save();
      cctx.translate(p.x, p.y);
      cctx.rotate(p.rot);
      cctx.fillStyle = p.color;
      cctx.fillRect(-p.size/2, -p.size/2, p.size, p.size * 0.6);
      cctx.restore();
    });
    confettiParticles = confettiParticles.filter(p => p.y < confettiCanvas.height + 30);
    if (now - start < duration || confettiParticles.length) {
      confettiRAF = requestAnimationFrame(frame);
    } else {
      cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
      confettiCanvas.style.display = 'none';
      confettiRAF = null;
    }
  }
  cancelAnimationFrame(confettiRAF);
  confettiRAF = requestAnimationFrame(frame);
}