<div class="row justify-content-center memory-game">
    <h1 class="col-12 text-center">Odd Fellows Memory Match Game</h1>
    <div class="col-12 text-center">
        <div id="game-board" class="game-board"></div>
        <div id="win-message" class="alert alert-info mt-3" style="display:none;">
            You matched them all! Nice work.
        </div>
        <button class="btn btn-outline-light mt-3" onclick="initGame()">New Game</button>
    </div>
</div>

<style>
    .game-board {
        display: grid;
        grid-template-columns: repeat(4, 100px);
        gap: 10px;
        margin: 30px auto;
        width: fit-content;
    }

    .memory-game .card {
        width: 100px;
        height: 100px;
        background: #444;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        text-align: center;
        cursor: pointer;
        user-select: none;
        border-radius: 5px;
        padding: 5px;
    }

    .memory-game .card.flipped { background: #2a7a2a; }

    @media (max-width: 480px) {
        .game-board {
            grid-template-columns: repeat(4, 72px);
            gap: 6px;
        }
        .memory-game .card { width: 72px; height: 72px; font-size: 11px; }
    }
</style>

<script>
    const values = ["Friendship", "Love", "Truth", "Fellowship", "Service", "Rebekah", "IOOF", "Charity"];

    function initGame() {
        const board = document.getElementById("game-board");
        board.innerHTML = '';
        document.getElementById("win-message").style.display = 'none';

        const memcards = [...values, ...values].sort(() => 0.5 - Math.random());
        let firstCard = null;
        let lock = false;
        let matched = 0;

        memcards.forEach(val => {
            const card = document.createElement("div");
            card.classList.add("card");
            card.dataset.value = val;
            board.appendChild(card);

            card.addEventListener("click", () => {
                if (lock || card.classList.contains("flipped")) return;

                card.textContent = val;
                card.classList.add("flipped");

                if (!firstCard) {
                    firstCard = card;
                } else {
                    if (firstCard.dataset.value === card.dataset.value) {
                        matched++;
                        firstCard = null;
                        if (matched === values.length) {
                            document.getElementById("win-message").style.display = 'block';
                        }
                    } else {
                        lock = true;
                        setTimeout(() => {
                            firstCard.textContent = "";
                            card.textContent = "";
                            firstCard.classList.remove("flipped");
                            card.classList.remove("flipped");
                            firstCard = null;
                            lock = false;
                        }, 1000);
                    }
                }
            });
        });
    }

    initGame();
</script>
