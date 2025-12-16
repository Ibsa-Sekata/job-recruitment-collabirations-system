// public/js/multi-step.js - minimal multi-step controller
document.addEventListener('DOMContentLoaded', function () {
    const steps = Array.from(document.querySelectorAll('.step'));
    if (!steps.length) return;
    let idx = 0;
    function show(i) {
        steps.forEach((s, j) => s.style.display = j === i ? 'block' : 'none');
    }
    document.querySelectorAll('.next').forEach(btn => btn.addEventListener('click', e => { idx = Math.min(idx + 1, steps.length - 1); show(idx); }));
    document.querySelectorAll('.prev').forEach(btn => btn.addEventListener('click', e => { idx = Math.max(idx - 1, 0); show(idx); }));
    show(0);
});
