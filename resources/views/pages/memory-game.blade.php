<div class="row justify-content-center memory-game">
    <h1 class="col-12 text-center mb-1">Odd Fellows Memory Match</h1>
    <p class="col-12 text-center mb-0" style="color:#556; font-size:.9rem;">
        <span id="moveCount">0</span> moves &nbsp;·&nbsp;
        <span id="pairCount">0</span> / 8 pairs
    </p>
    <div class="col-12 text-center">
        <div id="game-board" class="game-board"></div>
        <div id="win-message" class="win-message" style="display:none;">
            <span class="win-icon">✦</span>
            You matched them all!
            <span class="win-icon">✦</span>
            <div style="font-size:.85rem; margin-top:.4rem; color:#99bbaa;">
                Completed in <span id="finalMoves"></span> moves.
            </div>
        </div>
        <button class="new-game-btn" onclick="initGame()">New Game</button>
    </div>
</div>

<style>
.memory-game h1 { color: #ccddef; letter-spacing: .03em; }

.game-board {
    display: grid;
    grid-template-columns: repeat(4, 110px);
    gap: 12px;
    margin: 24px auto;
    width: fit-content;
}

/* ── Card wrapper provides the 3D perspective context ── */
.card-wrap {
    width: 110px;
    height: 110px;
    perspective: 900px;
    cursor: pointer;
}

/* ── Inner card rotates ── */
.mg-card {
    width: 100%;
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
    transition: transform .45s cubic-bezier(.4,0,.2,1);
    border-radius: 9px;
}

/* Hover: slight tilt + lift (only unflipped, unmatched) */
.card-wrap:not(.flipped):not(.matched):hover .mg-card {
    transform: rotateY(15deg) translateY(-3px) scale(1.04);
}
.card-wrap:not(.flipped):not(.matched) { transition: filter .15s; }
.card-wrap:not(.flipped):not(.matched):hover { filter: brightness(1.25); }

/* Flipped (selected, waiting) */
.card-wrap.flipped .mg-card   { transform: rotateY(180deg); }

/* Matched — keep flipped, add green pulse */
.card-wrap.matched .mg-card   { transform: rotateY(180deg); animation: matchPulse .4s ease-out; }

@keyframes matchPulse {
    0%   { transform: rotateY(180deg) scale(1); }
    40%  { transform: rotateY(180deg) scale(1.12); }
    100% { transform: rotateY(180deg) scale(1); }
}

/* ── Shared face styles ── */
.card-front, .card-back {
    position: absolute;
    inset: 0;
    border-radius: 9px;
    backface-visibility: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .02em;
}

/* ── Front face (hidden side) ── */
.card-front {
    background: linear-gradient(145deg, #1a2e42 0%, #0d1e30 100%);
    border: 1px solid #2a4a64;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
}
/* subtle chain-link watermark */
.card-front::after {
    content: '⬡';
    position: absolute;
    font-size: 52px;
    color: rgba(255,255,255,.04);
    pointer-events: none;
}

/* ── Back face (revealed side) ── */
.card-back {
    transform: rotateY(180deg);
    background: linear-gradient(145deg, #1a4a70 0%, #0d2a48 100%);
    border: 2px solid #4a8ab8;
    color: #cce4ff;
    box-shadow:
        0 0 0 1px rgba(74,138,184,.3),
        0 4px 16px rgba(74,138,184,.25);
}

/* Matched back face */
.card-wrap.matched .card-back {
    background: linear-gradient(145deg, #1a5a30 0%, #0d3a1c 100%);
    border: 2px solid #4aaa70;
    color: #aaddbb;
    box-shadow:
        0 0 0 1px rgba(74,170,112,.3),
        0 4px 16px rgba(74,170,112,.3);
    cursor: default;
}

/* ── Win message ── */
.win-message {
    background: linear-gradient(135deg, #0d2a18 0%, #091e10 100%);
    border: 1px solid #2a6a40;
    border-radius: 10px;
    color: #aaddbb;
    padding: 1rem 1.5rem;
    margin-top: .75rem;
    font-size: 1.1rem;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 0 24px rgba(74,170,112,.2);
    animation: winFadeIn .4s ease-out;
}
@keyframes winFadeIn {
    from { opacity: 0; transform: translateY(8px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);  }
}
.win-icon { color: #4aaa70; margin: 0 .3rem; }

/* ── New game button ── */
.new-game-btn {
    display: inline-block;
    margin-top: .9rem;
    padding: .45rem 1.4rem;
    background: transparent;
    border: 1px solid #2a4a64;
    border-radius: 6px;
    color: #7aaace;
    font-size: .88rem;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.new-game-btn:hover {
    background: #162840;
    border-color: #4a8ab8;
    color: #cce4ff;
}

/* ── Responsive ── */
@media (max-width: 520px) {
    .game-board { grid-template-columns: repeat(4, 76px); gap: 8px; }
    .card-wrap, .card-front, .card-back { width: 76px; height: 76px; }
    .card-wrap { width: 76px; height: 76px; }
    .card-front::after { font-size: 36px; }
    .card-front, .card-back { font-size: 11px; }
}
</style>

<script>
const values = ["Friendship","Love","Truth","Fellowship","Service","Rebekah","I.O.O.F.","Charity"];

function initGame() {
    const board = document.getElementById("game-board");
    board.innerHTML = '';
    document.getElementById("win-message").style.display = 'none';
    document.getElementById("moveCount").textContent = '0';
    document.getElementById("pairCount").textContent = '0';

    const deck = [...values, ...values].sort(() => Math.random() - .5);
    let first = null, lock = false, moves = 0, pairs = 0;

    deck.forEach(val => {
        const wrap  = document.createElement("div");
        wrap.className = "card-wrap";

        const inner = document.createElement("div");
        inner.className = "mg-card";

        const front = document.createElement("div");
        front.className = "card-front";

        const back = document.createElement("div");
        back.className = "card-back";
        back.textContent = val;

        inner.append(front, back);
        wrap.appendChild(inner);
        board.appendChild(wrap);

        wrap.addEventListener("click", () => {
            if (lock || wrap.classList.contains("flipped") || wrap.classList.contains("matched")) return;

            wrap.classList.add("flipped");

            if (!first) {
                first = wrap;
            } else {
                lock = true;
                moves++;
                document.getElementById("moveCount").textContent = moves;

                if (first.querySelector(".card-back").textContent === val) {
                    // Match
                    pairs++;
                    document.getElementById("pairCount").textContent = pairs;
                    first.classList.replace("flipped", "matched");
                    wrap.classList.replace("flipped", "matched");
                    first = null;
                    lock = false;
                    if (pairs === values.length) {
                        document.getElementById("finalMoves").textContent = moves;
                        document.getElementById("win-message").style.display = 'inline-block';
                    }
                } else {
                    // No match — flip back after delay
                    const missed = first;
                    setTimeout(() => {
                        missed.classList.remove("flipped");
                        wrap.classList.remove("flipped");
                        first = null;
                        lock = false;
                    }, 1050);
                }
            }
        });
    });
}

initGame();
</script>
